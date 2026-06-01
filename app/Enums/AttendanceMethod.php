<?php

namespace App\Enums;

enum AttendanceMethod: string
{
    case AdminScanMemberQr = 'admin_scan_member_qr';
    case MemberScanEventQr = 'member_scan_event_qr';
    case ManualEntry = 'manual_entry';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
