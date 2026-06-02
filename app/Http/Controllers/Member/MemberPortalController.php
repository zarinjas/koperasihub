<?php

namespace App\Http\Controllers\Member;

use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

abstract class MemberPortalController extends Controller
{
    protected function activeCooperativeId(Request $request): ?int
    {
        return $request->user()?->cooperative_id;
    }

    protected function currentUser(Request $request): User
    {
        return $request->user();
    }

    protected function currentMemberOrNull(Request $request): ?Member
    {
        $member = $this->currentUser($request)->member;

        if (! $member) {
            return null;
        }

        abort_unless($member->cooperative_id === $this->activeCooperativeId($request), 404);

        return $member;
    }

    protected function currentMember(Request $request): Member
    {
        $member = $this->currentMemberOrNull($request);
        abort_unless($member, 404);

        return $member;
    }

    protected function formatBytes(?int $bytes): string
    {
        if (! $bytes) {
            return '-';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1024, 0).' KB';
    }

    protected function genderLabel(?string $gender): ?string
    {
        return match ($gender) {
            'male' => 'Lelaki',
            'female' => 'Perempuan',
            default => $gender,
        };
    }

    protected function maritalStatusLabel(?string $status): ?string
    {
        return match ($status) {
            'single' => 'Belum Berkahwin',
            'married' => 'Berkahwin',
            'divorced' => 'Bercerai',
            'widowed' => 'Balu / Duda',
            default => null,
        };
    }
}