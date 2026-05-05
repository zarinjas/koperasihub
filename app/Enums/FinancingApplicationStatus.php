<?php

namespace App\Enums;

enum FinancingApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
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

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Dihantar',
            self::GuarantorPending => 'Menunggu Persetujuan Penjamin',
            self::GuarantorAccepted => 'Penjamin Bersetuju',
            self::GuarantorRejected => 'Penjamin Ditolak',
            self::UnderReview => 'Dalam Semakan',
            self::IncompleteDocuments => 'Dokumen Tidak Lengkap',
            self::Approved => 'Diluluskan',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::Closed => 'Ditutup',
        };
    }
}
