<?php

namespace App\Enums;

enum DocumentVisibility: string
{
    case Public = 'public';
    case MembersOnly = 'members_only';
    case AdminOnly = 'admin_only';
    case SpecificMember = 'specific_member';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
