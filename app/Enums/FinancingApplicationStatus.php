<?php

namespace App\Enums;

enum FinancingApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case PendingCompletedForm = 'pending_completed_form';
    case GuarantorPending = 'guarantor_pending';
    case GuarantorAccepted = 'guarantor_accepted';
    case GuarantorRejected = 'guarantor_rejected';
    case UnderReview = 'under_review';
    case IncompleteDocuments = 'incomplete_documents';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function memberCancellable(): array
    {
        return [
            self::Draft,
            self::Submitted,
            self::GuarantorPending,
            self::GuarantorAccepted,
            self::PendingCompletedForm,
            self::IncompleteDocuments,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Dihantar',
            self::PendingCompletedForm => 'Menunggu Borang Lengkap',
            self::GuarantorPending => 'Menunggu Maklum Balas Penjamin',
            self::GuarantorAccepted => 'Sedia Untuk Semakan',
            self::GuarantorRejected => 'Penjamin Tidak Bersetuju',
            self::UnderReview => 'Dalam Semakan',
            self::IncompleteDocuments => 'Dokumen Tambahan Diperlukan',
            self::Approved => 'Diluluskan',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::Closed => 'Ditutup',
        };
    }
}
