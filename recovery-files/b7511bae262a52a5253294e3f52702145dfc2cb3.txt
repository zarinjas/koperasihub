<?php

namespace App\Enums;

enum ProgramType: string
{
    case Physical = 'physical';
    case Online = 'online';
    case Hybrid = 'hybrid';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
