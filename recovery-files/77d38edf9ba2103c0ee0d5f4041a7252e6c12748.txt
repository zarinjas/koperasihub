<?php

namespace App\Enums;

enum AnsuranProductStatus: string
{
    case Draft = 'draf';
    case Active = 'aktif';
    case Inactive = 'tidak_aktif';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Active => 'Aktif',
            self::Inactive => 'Tidak Aktif',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'green',
            self::Inactive => 'red',
        };
    }
}
