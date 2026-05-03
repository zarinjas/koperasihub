<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;
use App\Support\AccessControl;

class ComplaintPolicy
{
    public function viewMember(User $user, Complaint $complaint): bool
    {
        if (! $user->can(AccessControl::PERMISSION_MEMBER_ACCESS)) {
            return false;
        }

        return $complaint->cooperative_id === $user->cooperative_id
            && $complaint->created_by === $user->id;
    }
}
