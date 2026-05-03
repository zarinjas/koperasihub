<?php

namespace App\Policies;

use App\Models\MembershipApplication;
use App\Models\User;
use App\Support\AccessControl;

class MembershipApplicationPolicy
{
    public function viewMember(User $user, MembershipApplication $application): bool
    {
        $member = $user->member;

        return $member
            && $user->can(AccessControl::PERMISSION_MEMBER_ACCESS)
            && $application->cooperative_id === $user->cooperative_id
            && $application->approved_member_id === $member->id;
    }
}
