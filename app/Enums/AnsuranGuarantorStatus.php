<?php

namespace App\Enums;

enum AnsuranGuarantorStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Maklum Balas',
            self::Accepted => 'Bersetuju Menjadi Penjamin',
            self::Rejected => 'Tidak Bersetuju',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'orange',
            self::Accepted => 'green',
            self::Rejected => 'red',
        };
    }
}
