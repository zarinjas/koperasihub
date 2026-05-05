<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use App\Support\AccessControl;

class MemberPolicy
{
    public function viewPortal(User $user, Member $member): bool
    {
        return $this->isOwnMemberRecord($user, $member);
    }

    public function updateProfile(User $user, Member $member): bool
    {
        return $this->isOwnMemberRecord($user, $member);
    }

    public function viewCard(User $user, Member $member): bool
    {
        return $this->isOwnMemberRecord($user, $member);
    }

    private function isOwnMemberRecord(User $user, Member $member): bool
    {
        return $user->can(AccessControl::PERMISSION_MEMBER_ACCESS)
            && $member->user_id === $user->id
            && $member->cooperative_id === $user->cooperative_id;
    }
}
