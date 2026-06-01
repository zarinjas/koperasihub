<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Announcement $announcement,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->announcement->send_email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->announcement->title)
            ->greeting('Salam sejahtera,')
            ->line($this->announcement->summary ?? strip_tags((string) $this->announcement->content))
            ->action('Lihat Pengumuman', route('member.announcements.index'))
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'announcement_id' => $this->announcement->id,
            'title' => $this->announcement->title,
            'summary' => Str::limit(
                $this->announcement->summary ?? strip_tags((string) $this->announcement->content),
                140,
            ),
            'audience' => $this->announcement->audience->value,
            'url' => $this->announcement->audience->value === 'public'
                ? route('public.announcements.show', $this->announcement->slug)
                : route('member.announcements.index'),
        ];
    }
}