<?php

namespace App\Enums;

enum NewsCategory: string
{
    case General = 'general';
    case Announcement = 'announcement';
    case Event = 'event';
    case Achievement = 'achievement';
    case Community = 'community';
    case Education = 'education';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
