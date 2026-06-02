<?php

namespace App\Enums;

enum AnsuranPaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Partial = 'partial';
    case Overdue = 'overdue';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum Dibayar',
            self::Paid => 'Telah Dibayar',
            self::Partial => 'Sebahagian Dibayar',
            self::Overdue => 'Tertunggak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Paid => 'green',
            self::Partial => 'orange',
            self::Overdue => 'red',
        };
    }
}