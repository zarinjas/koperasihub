<?php

namespace App\Enums;

enum FinancingCategoryType: string
{
    case Guaranteed = 'guaranteed';
    case NonGuaranteed = 'non_guaranteed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Guaranteed => 'Berpenjamin',
            self::NonGuaranteed => 'Tanpa Penjamin',
        };
    }
}
