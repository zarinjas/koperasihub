<?php

namespace App\Enums;

enum FormSubmissionMethod: string
{
    case OnlineOnly = 'online_only';
    case RequiresStampedUpload = 'requires_stamped_upload';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::OnlineOnly => 'Hantar Online Sahaja',
            self::RequiresStampedUpload => 'Perlu Borang Bercop',
        };
    }
}
