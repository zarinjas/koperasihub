<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class AnnouncementNotificationService
{
    public function send(Announcement $announcement): void
    {
        if (! $announcement->send_notification) {
            return;
        }

        $recipients = $this->resolveRecipients($announcement);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AnnouncementNotification($announcement));
    }

    private function resolveRecipients(Announcement $announcement): Collection
    {
        if ($announcement->relationLoaded('specificMembers') && $announcement->specificMembers->isNotEmpty()) {
            return User::query()
                ->whereIn('id', $announcement->specificMembers->pluck('user_id')->filter())
                ->where('status', 'active')
                ->get();
        }

        if ($announcement->specificMembers()->exists()) {
            $memberIds = $announcement->specificMembers()->pluck('member_id');

            return User::query()
                ->whereIn('id', function ($query) use ($memberIds): void {
                    $query->select('user_id')
                        ->from('members')
                        ->whereIn('id', $memberIds)
                        ->whereNotNull('user_id');
                })
                ->where('status', 'active')
                ->get();
        }

        return match ($announcement->audience->value) {
            'members' => User::query()
                ->where('user_type', 'member')
                ->where('status', 'active')
                ->get(),
            'admins' => User::query()
                ->whereIn('user_type', ['super_admin', 'admin'])
                ->where('status', 'active')
                ->get(),
            'public' => User::query()
                ->whereIn('user_type', ['member', 'super_admin', 'admin'])
                ->where('status', 'active')
                ->get(),
            default => new Collection(),
        };
    }
}