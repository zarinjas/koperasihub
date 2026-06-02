<?php

namespace App\Enums;

enum FormSubmissionStatus: string
{
    case Draft = 'draft';
    case PendingStampUpload = 'pending_stamp_upload';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case IncompleteDocuments = 'incomplete_documents';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Closed = 'closed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::PendingStampUpload => 'Menunggu Borang Bercop',
            self::Submitted => 'Dihantar',
            self::UnderReview => 'Dalam Proses',
            self::IncompleteDocuments => 'Dokumen Tidak Lengkap',
            self::Approved => 'Diluluskan',
            self::Rejected => 'Ditolak',
            self::Closed => 'Ditutup',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::PendingStampUpload => 'amber',
            self::Submitted => 'blue',
            self::UnderReview => 'amber',
            self::IncompleteDocuments => 'red',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Closed => 'slate',
        };
    }
}