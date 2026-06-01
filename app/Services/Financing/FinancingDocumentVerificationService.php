<?php

namespace App\Services\Financing;

use App\Models\FinancingGeneratedDocument;
use App\Models\User;

class FinancingDocumentVerificationService
{
    public function verify(FinancingGeneratedDocument $document, User $actor): FinancingGeneratedDocument
    {
        $from = $document->status;

        $document->update([
            'status' => FinancingGeneratedDocument::STATUS_VERIFIED,
            'verified_by' => $actor->id,
            'verified_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        $this->recordEvent($document->fresh(), 'document_verified', $from, FinancingGeneratedDocument::STATUS_VERIFIED);

        return $document->fresh();
    }

    public function reject(FinancingGeneratedDocument $document, User $actor, string $reason): FinancingGeneratedDocument
    {
        $from = $document->status;

        $document->update([
            'status' => FinancingGeneratedDocument::STATUS_REJECTED,
            'rejected_by' => $actor->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $this->recordEvent($document->fresh(), 'document_rejected', $from, FinancingGeneratedDocument::STATUS_REJECTED, $reason);

        return $document->fresh();
    }

    private function recordEvent(FinancingGeneratedDocument $document, string $action, ?string $from, ?string $to, ?string $notes = null): void
    {
        $document->events()->create([
            'cooperative_id' => $document->cooperative_id,
            'financing_application_id' => $document->financing_application_id,
            'actor_id' => auth()->id(),
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}
