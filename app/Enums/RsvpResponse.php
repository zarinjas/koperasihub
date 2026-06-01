<?php

namespace App\Enums;

enum RsvpResponse: string
{
    case Hadir = 'hadir';
    case TidakHadir = 'tidak_hadir';
    case Mungkin = 'mungkin';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
