<?php

namespace App\Enums;

enum AnsuranDeliveryMethod: string
{
    case Pickup = 'pickup';
    case Delivery = 'delivery';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Pickup => 'Ambil Sendiri',
            self::Delivery => 'Penghantaran',
        };
    }
}
