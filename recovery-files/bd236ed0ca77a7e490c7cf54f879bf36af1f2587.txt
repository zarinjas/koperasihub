<?php

namespace App\Services\Financing;

use App\Models\FinancingGeneratedDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FinancingDocumentUploadService
{
    public function upload(FinancingGeneratedDocument $document, UploadedFile $file): FinancingGeneratedDocument
    {
        $from = $document->status;

        if ($document->uploaded_path) {
            Storage::disk('public')->delete($document->uploaded_path);
        }

        $path = $file->store('financing/signed-documents/'.$document->financing_application_id, 'public');

        $document->update([
            'uploaded_path' => $path,
            'uploaded_original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_at' => now(),
            'verified_by' => null,
            'verified_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'status' => FinancingGeneratedDocument::STATUS_UPLOADED,
        ]);

        $this->recordEvent($document->fresh(), 'document_uploaded', $from, FinancingGeneratedDocument::STATUS_UPLOADED);

        return $document->fresh();
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
