<?php

namespace App\Enums;

enum FormFieldDisplayMode: string
{
    case OnlineAndPrint = 'online_and_print';
    case OnlineOnly = 'online_only';
    case PrintOnly = 'print_only';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function showsOnline(): bool
    {
        return in_array($this, [self::OnlineAndPrint, self::OnlineOnly], true);
    }

    public function showsPrint(): bool
    {
        return in_array($this, [self::OnlineAndPrint, self::PrintOnly], true);
    }
}