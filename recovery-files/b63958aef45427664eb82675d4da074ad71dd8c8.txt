<?php

namespace App\Http\Controllers\Member\Financing;

use App\Http\Controllers\Member\MemberPortalController;
use App\Models\FinancingApplication;
use App\Models\FinancingGeneratedDocument;
use App\Services\Financing\FinancingDocumentGenerationService;
use App\Services\Financing\FinancingDocumentUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancingGeneratedDocumentController extends MemberPortalController
{
    public function __construct(
        private readonly FinancingDocumentGenerationService $generator,
        private readonly FinancingDocumentUploadService $uploads,
    ) {}

    public function download(Request $request, FinancingApplication $application, FinancingGeneratedDocument $document): StreamedResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id, 404);
        abort_unless($document->financing_application_id === $application->id, 404);

        if (! $document->generated_path) {
            $document = $this->generator->generate($document);
        }

        abort_unless($document->generated_path && Storage::disk('public')->exists($document->generated_path), 404);

        $document->update(['downloaded_at' => now()]);

        return Storage::disk('public')->download(
            $document->generated_path,
            $this->downloadName($document, $document->generated_path)
        );
    }

    public function upload(Request $request, FinancingApplication $application, FinancingGeneratedDocument $document): RedirectResponse
    {
        $member = $this->currentMember($request);
        abort_unless($application->member_id === $member->id, 404);
        abort_unless($document->financing_application_id === $application->id, 404);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $this->uploads->upload($document, $validated['file']);

        return back()->with('status', 'Dokumen berjaya dimuat naik.');
    }

    private function downloadName(FinancingGeneratedDocument $document, string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'html';

        return str($document->document_name)->slug()->append('.'.$extension)->toString();
    }
}
