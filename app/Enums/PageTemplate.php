<?php

namespace App\Enums;

enum PageTemplate: string
{
    case Default = 'default';
    case Homepage = 'homepage';
    case Landing = 'landing';
    case Service = 'service';
    case Contact = 'contact';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
