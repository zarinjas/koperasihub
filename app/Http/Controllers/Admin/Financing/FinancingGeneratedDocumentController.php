<?php

namespace App\Http\Controllers\Admin\Financing;

use App\Http\Controllers\Controller;
use App\Models\FinancingApplication;
use App\Models\FinancingGeneratedDocument;
use App\Services\Financing\FinancingDocumentGenerationService;
use App\Services\Financing\FinancingDocumentVerificationService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancingGeneratedDocumentController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly FinancingDocumentGenerationService $generator,
        private readonly FinancingDocumentVerificationService $verification,
    ) {}

    public function downloadGenerated(Request $request, FinancingApplication $application, FinancingGeneratedDocument $document): StreamedResponse
    {
        $this->ensureVisible($application, $document);

        if (! $document->generated_path) {
            $document = $this->generator->generate($document);
        }

        abort_unless($document->generated_path && Storage::disk('public')->exists($document->generated_path), 404);

        return Storage::disk('public')->download($document->generated_path);
    }

    public function downloadUploaded(FinancingApplication $application, FinancingGeneratedDocument $document): StreamedResponse
    {
        $this->ensureVisible($application, $document);
        abort_unless($document->uploaded_path && Storage::disk('public')->exists($document->uploaded_path), 404);

        return Storage::disk('public')->download(
            $document->uploaded_path,
            $document->uploaded_original_name ?: basename($document->uploaded_path)
        );
    }

    public function verify(Request $request, FinancingApplication $application, FinancingGeneratedDocument $document): RedirectResponse
    {
        $this->ensureVisible($application, $document);
        abort_unless($document->uploaded_path, 422, 'Dokumen belum dimuat naik.');

        $this->verification->verify($document, $request->user());

        return back()->with('status', 'Dokumen telah disahkan.');
    }

    public function reject(Request $request, FinancingApplication $application, FinancingGeneratedDocument $document): RedirectResponse
    {
        $this->ensureVisible($application, $document);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $this->verification->reject($document, $request->user(), $validated['reason']);

        return back()->with('status', 'Dokumen telah ditolak.');
    }

    private function ensureVisible(FinancingApplication $application, FinancingGeneratedDocument $document): void
    {
        abort_unless($application->cooperative_id === $this->settings->activeCooperative()?->id, 404);
        abort_unless($document->financing_application_id === $application->id, 404);
    }
}
