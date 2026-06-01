<?php

namespace App\Services\Financing;

use App\Models\FinancingApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class FinancingPrintPackageService
{
    public function __construct(
        private readonly FinancingFieldMappingService $mapping,
    ) {}

    public function package(FinancingApplication $application): array
    {
        $application->load([
            'generatedDocuments',
            'documents',
            'guarantors.guarantorMember.user',
            'member.user',
            'product',
            'category',
        ]);

        $files = [];
        $index = 0;

        // 1. Generate package summary PDF
        $summaryPdf = $this->generateSummaryPdf($application);
        if ($summaryPdf) {
            $files[] = [
                'index' => sprintf('%02d', $index++),
                'name' => 'Ringkasan Permohonan',
                'filename' => 'ringkasan-permohonan.pdf',
                'content' => $summaryPdf,
            ];
        }

        // 2. Generated documents (prioritize uploaded signed versions)
        foreach ($application->generatedDocuments as $doc) {
            $docName = $doc->document_name ?: $doc->document_code;
            $baseName = Str::slug($docName);

            // Priority: uploaded (signed) → generated (html → pdf) → skip
            if ($doc->uploaded_path && Storage::disk('public')->exists($doc->uploaded_path)) {
                $files[] = [
                    'index' => sprintf('%02d', $index++),
                    'name' => $docName.' (Ditandatangani)',
                    'filename' => $baseName.'-ditandatangani.pdf',
                    'content' => Storage::disk('public')->get($doc->uploaded_path),
                ];
            } elseif ($doc->generated_path && Storage::disk('public')->exists($doc->generated_path)) {
                $html = Storage::disk('public')->get($doc->generated_path);
                $pdf = $this->renderHtmlToPdf($html, $docName);
                if ($pdf) {
                    $files[] = [
                        'index' => sprintf('%02d', $index++),
                        'name' => $docName.' (Dijana)',
                        'filename' => $baseName.'.pdf',
                        'content' => $pdf,
                    ];
                }
            }
        }

        // 3. Supporting uploaded documents
        foreach ($application->documents as $doc) {
            if (! $doc->file_path || ! Storage::disk('public')->exists($doc->file_path)) {
                continue;
            }
            $ext = pathinfo($doc->file_path, PATHINFO_EXTENSION) ?: 'pdf';
            $files[] = [
                'index' => sprintf('%02d', $index++),
                'name' => $doc->label,
                'filename' => Str::slug($doc->label).'.'.$ext,
                'content' => Storage::disk('public')->get($doc->file_path),
            ];
        }

        // 4. Stamped form (if uploaded)
        if ($application->stamped_form_path && Storage::disk('public')->exists($application->stamped_form_path)) {
            $ext = pathinfo($application->stamped_form_path, PATHINFO_EXTENSION) ?: 'pdf';
            $files[] = [
                'index' => sprintf('%02d', $index++),
                'name' => 'Borang Bercop',
                'filename' => 'borang-bercop.'.$ext,
                'content' => Storage::disk('public')->get($application->stamped_form_path),
            ];
        }

        // 5. Guarantor signatures (if not already in generated docs)
        foreach ($application->guarantors as $g) {
            if ($g->signature_path && Storage::disk('public')->exists($g->signature_path)) {
                $name = $g->guarantorMember?->full_name ?: 'Penjamin';
                $files[] = [
                    'index' => sprintf('%02d', $index++),
                    'name' => 'Tandatangan '.$name,
                    'filename' => 'tandatangan-'.Str::slug($name).'.png',
                    'content' => Storage::disk('public')->get($g->signature_path),
                ];
            }
        }

        return $this->buildZip($application, $files);
    }

    private function generateSummaryPdf(FinancingApplication $application): ?string
    {
        try {
            $shared = app(\App\Services\Settings\SettingsService::class)->shared();
            $cooperative = $shared['cooperative'] ?? [];
            $contact = $shared['contact'] ?? [];

            $memberName = $application->member?->full_name ?? $application->member?->user?->name ?? '-';
            $guarantors = $application->guarantors?->map(fn ($g) => [
                'name' => $g->guarantorMember?->full_name ?? '-',
                'status_label' => $g->status->label(),
            ]) ?? collect();

            $pdf = Pdf::loadView('financing.print-package-summary', [
                'cooperative' => $cooperative,
                'contact' => $contact,
                'application' => $application,
                'memberName' => $memberName,
                'guarantors' => $guarantors,
            ]);

            $pdf->setPaper('A4');
            $pdf->setOptions(['dpi' => 120, 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

            return $pdf->output();
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    private function renderHtmlToPdf(string $html, string $title): ?string
    {
        try {
            $wrapped = view('financing.print-document-wrapper', [
                'title' => $title,
                'content' => $html,
            ])->render();

            $pdf = Pdf::loadHtml($wrapped);
            $pdf->setPaper('A4');
            $pdf->setOptions(['dpi' => 120, 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

            return $pdf->output();
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    private function buildZip(FinancingApplication $application, array $files): array
    {
        $refNo = $application->reference_no ?: 'permohonan';
        $timestamp = now()->format('Ymd-His');
        $zipName = "pembiayaan-{$refNo}-{$timestamp}.zip";
        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir.'/'.$zipName;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Cannot create ZIP archive at: '.$zipPath);
        }

        foreach ($files as $file) {
            $zip->addFromString($file['index'].'-'.$file['filename'], $file['content']);
        }

        $zip->close();

        return [
            'zip_path' => $zipPath,
            'zip_name' => $zipName,
            'file_count' => count($files),
        ];
    }
}
