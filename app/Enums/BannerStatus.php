<?php

namespace App\Enums;

enum BannerStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
