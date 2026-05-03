<?php

namespace App\Policies;

use App\Enums\DocumentVisibility;
use App\Models\Document;
use App\Models\User;
use App\Support\AccessControl;

class DocumentPolicy
{
    public function viewMember(User $user, Document $document): bool
    {
        $member = $user->member;

        if (! $user->can(AccessControl::PERMISSION_MEMBER_ACCESS)) {
            return false;
        }

        if (
            $document->cooperative_id !== $user->cooperative_id
            || $document->status->value !== 'published'
            || ($document->published_at && $document->published_at->isFuture())
            || ($document->expires_at && ! $document->expires_at->isFuture())
        ) {
            return false;
        }

        return match ($document->visibility) {
            DocumentVisibility::MembersOnly => true,
            DocumentVisibility::SpecificMember => $member && $document->member_id === $member->id,
            default => false,
        };
    }
}
