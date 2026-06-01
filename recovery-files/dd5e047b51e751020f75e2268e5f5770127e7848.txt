<?php

namespace App\Services\Financing;

use App\Models\FinancingGeneratedDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FinancingDocumentGenerationService
{
    public function __construct(
        private readonly FinancingFieldMappingService $fieldMapping,
    ) {}

    public function generate(FinancingGeneratedDocument $document): FinancingGeneratedDocument
    {
        $document->loadMissing(['application.member', 'application.product', 'template']);

        if ($document->source_type === 'manual_upload_only') {
            $document->update(['status' => FinancingGeneratedDocument::STATUS_PENDING_UPLOAD]);

            return $document;
        }

        if ($document->source_type === 'pdf_upload' && $document->template?->template_path) {
            $document->update([
                'generated_path' => $document->template->template_path,
                'generated_at' => now(),
                'status' => $document->requires_upload
                    ? FinancingGeneratedDocument::STATUS_PENDING_UPLOAD
                    : FinancingGeneratedDocument::STATUS_GENERATED,
            ]);

            return $document;
        }

        $map = $this->fieldMapping->build($document->application);
        $body = $document->template?->html_template
            ? $this->fieldMapping->replacePlaceholders($document->template->html_template, $map)
            : view('financing.documents.generated', [
                'document' => $document,
                'application' => $document->application,
                'map' => $map,
            ])->render();

        $path = sprintf(
            'financing/generated-documents/%d/%s-%s.html',
            $document->financing_application_id,
            Str::slug($document->document_code),
            now()->format('YmdHis')
        );

        Storage::disk('public')->put($path, $body);

        $document->update([
            'generated_path' => $path,
            'generated_at' => now(),
            'status' => $document->requires_upload
                ? FinancingGeneratedDocument::STATUS_PENDING_UPLOAD
                : FinancingGeneratedDocument::STATUS_GENERATED,
        ]);

        return $document;
    }
}
