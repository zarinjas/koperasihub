<?php

namespace App\Enums;

enum FinancingApplicationStatus: string
{
    case Draft = 'draf';
    case Submitted = 'dihantar';
    case PendingGuarantor = 'menunggu_penjamin';
    case PendingUpload = 'menunggu_muat_naik';
    case InReview = 'dalam_proses';
    case Incomplete = 'dokumen_tidak_lengkap';
    case Approved = 'berjaya';
    case Rejected = 'ditolak';
    case Cancelled = 'dibatalkan';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function memberCancellable(): array
    {
        return [
            self::Draft,
            self::Submitted,
            self::PendingGuarantor,
            self::PendingUpload,
        ];
    }

    public static function active(): array
    {
        return [
            self::Submitted,
            self::PendingGuarantor,
            self::PendingUpload,
            self::InReview,
            self::Incomplete,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Dihantar',
            self::PendingGuarantor => 'Menunggu Penjamin',
            self::PendingUpload => 'Menunggu Muat Naik Balik Daripada Ahli',
            self::InReview => 'Dalam Proses',
            self::Incomplete => 'Dokumen Tidak Lengkap',
            self::Approved => 'Berjaya',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'blue',
            self::PendingGuarantor => 'orange',
            self::PendingUpload => 'yellow',
            self::InReview => 'purple',
            self::Incomplete => 'red',
            self::Approved => 'green',
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
            self::Draft => [self::Submitted, self::Cancelled],
            self::Submitted => [self::PendingGuarantor, self::PendingUpload, self::InReview, self::Cancelled],
            self::PendingGuarantor => [self::Submitted, self::PendingUpload, self::Cancelled],
            self::PendingUpload => [self::InReview, self::Cancelled],
            self::InReview => [self::Approved, self::Rejected, self::Incomplete, self::Cancelled],
            self::Incomplete => [self::InReview, self::Cancelled],
            self::Approved, self::Rejected, self::Cancelled => [],
        };
    }
}