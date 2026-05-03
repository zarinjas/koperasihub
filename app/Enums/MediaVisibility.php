<?php

namespace App\Enums;

enum MediaVisibility: string
{
    case Public = 'public';
    case MembersOnly = 'members_only';
    case AdminOnly = 'admin_only';
    case Private = 'private';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
