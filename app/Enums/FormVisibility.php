<?php

namespace App\Enums;

enum FormVisibility: string
{
    case Public = 'public';
    case MembersOnly = 'members_only';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
