<?php

namespace App\Enums;

enum AnsuranDeliveryStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case ReadyForPickup = 'ready_for_pickup';
    case PickedUp = 'picked_up';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Processing => 'Sedang Diproses',
            self::Shipped => 'Dalam Penghantaran',
            self::Delivered => 'Telah Dihantar',
            self::ReadyForPickup => 'Sedia Diambil',
            self::PickedUp => 'Telah Diambil',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Processing => 'blue',
            self::Shipped => 'orange',
            self::Delivered, self::PickedUp => 'green',
            self::ReadyForPickup => 'yellow',
        };
    }
}