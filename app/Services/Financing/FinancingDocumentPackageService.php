<?php

namespace App\Services\Financing;

use App\Models\FinancingApplication;
use App\Models\FinancingDocumentTemplate;
use App\Models\FinancingGeneratedDocument;
use Illuminate\Support\Facades\DB;

class FinancingDocumentPackageService
{
    public function __construct(
        private readonly FinancingDocumentGenerationService $generator,
    ) {}

    public function createForApplication(FinancingApplication $application): void
    {
        DB::transaction(function () use ($application) {
            $application->loadMissing(['product.documentTemplates']);

            $templates = $application->product?->documentTemplates()
                ->active()
                ->ordered()
                ->get() ?? collect();

            if ($templates->isEmpty()) {
                $templates = $this->legacyTemplates($application);
            }

            foreach ($templates as $template) {
                $document = FinancingGeneratedDocument::firstOrCreate(
                    [
                        'financing_application_id' => $application->id,
                        'document_code' => $template->code,
                    ],
                    [
                        'cooperative_id' => $application->cooperative_id,
                        'financing_document_template_id' => $template instanceof FinancingDocumentTemplate ? $template->id : null,
                        'document_name' => $template->name,
                        'document_type' => $template->type,
                        'source_type' => $template->source_type,
                        'status' => FinancingGeneratedDocument::STATUS_PENDING_GENERATION,
                        'requires_upload' => (bool) $template->requires_upload,
                        'requires_verification' => (bool) $template->requires_verification,
                        'metadata_json' => $template instanceof FinancingDocumentTemplate ? null : ['legacy' => true],
                    ]
                );

                if ($document->status === FinancingGeneratedDocument::STATUS_PENDING_GENERATION) {
                    if (! $template instanceof FinancingDocumentTemplate && $template->source_type === 'pdf_upload' && ! empty($template->template_path)) {
                        $document->update([
                            'generated_path' => $template->template_path,
                            'generated_at' => now(),
                            'status' => $document->requires_upload
                                ? FinancingGeneratedDocument::STATUS_PENDING_UPLOAD
                                : FinancingGeneratedDocument::STATUS_GENERATED,
                        ]);
                        $this->recordEvent($document->fresh(), 'document_generated', null, $document->fresh()->status);

                        continue;
                    }

                    $this->generator->generate($document);
                    $this->recordEvent($document, 'document_generated', null, $document->fresh()->status);
                }
            }
        });
    }

    private function legacyTemplates(FinancingApplication $application)
    {
        $items = collect();

        if ($application->product?->form_template_path) {
            $items->push((object) [
                'code' => 'legacy_product_form',
                'name' => $application->product->form_template_name ?: 'Borang Produk',
                'type' => 'application_form',
                'source_type' => 'pdf_upload',
                'template_path' => $application->product->form_template_path,
                'requires_upload' => true,
                'requires_verification' => true,
            ]);
        }

        if ($application->product?->requires_stamped_upload || $items->isEmpty()) {
            $items->push((object) [
                'code' => 'application_summary',
                'name' => 'Borang Permohonan Pembiayaan',
                'type' => 'application_form',
                'source_type' => 'html',
                'requires_upload' => (bool) $application->product?->requires_stamped_upload,
                'requires_verification' => (bool) $application->product?->requires_stamped_upload,
            ]);
        }

        $guarantorCount = $application->guarantors()->count();
        if ($guarantorCount > 0) {
            $items->push((object) [
                'code' => 'guarantor_consent_form',
                'name' => 'Borang Persetujuan Penjamin',
                'type' => 'guarantor_form',
                'source_type' => 'html',
                'requires_upload' => true,
                'requires_verification' => true,
            ]);
        }

        return $items;
    }

    private function recordEvent(FinancingGeneratedDocument $document, string $action, ?string $from, ?string $to): void
    {
        $document->events()->create([
            'cooperative_id' => $document->cooperative_id,
            'financing_application_id' => $document->financing_application_id,
            'actor_id' => auth()->id(),
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'created_at' => now(),
        ]);
    }
}