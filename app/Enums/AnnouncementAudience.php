<?php

namespace App\Enums;

enum AnnouncementAudience: string
{
    case Public = 'public';
    case Members = 'members';
    case Admins = 'admins';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
