<?php

namespace App\Enums;

enum AnsuranApplicationStatus: string
{
    case PendingGuarantor = 'pending_guarantor';
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case AgreementGenerated = 'agreement_generated';
    case Signed = 'signed';
    case Processing = 'processing';
    case Completed = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingGuarantor => 'Menunggu Penjamin',
            self::Pending => 'Menunggu Semakan',
            self::UnderReview => 'Dalam Proses',
            self::Approved => 'Diluluskan',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::AgreementGenerated => 'Perjanjian Sedia',
            self::Signed => 'Ditandatangani',
            self::Processing => 'Sedang Diproses',
            self::Completed => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingGuarantor => 'orange',
            self::Pending => 'blue',
            self::UnderReview => 'purple',
            self::Approved, self::AgreementGenerated => 'yellow',
            self::Signed, self::Processing => 'blue',
            self::Completed => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'gray',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingGuarantor => [self::Pending, self::Rejected, self::Cancelled],
            self::Pending => [self::UnderReview, self::Rejected, self::Cancelled],
            self::UnderReview => [self::Approved, self::Rejected, self::Cancelled],
            self::Approved => [self::AgreementGenerated, self::Rejected, self::Cancelled],
            self::AgreementGenerated => [self::Signed, self::Rejected, self::Cancelled],
            self::Signed => [self::Processing, self::Rejected, self::Cancelled],
            self::Processing => [self::Completed, self::Cancelled],
            self::Rejected, self::Cancelled, self::Completed => [],
        };
    }
}
