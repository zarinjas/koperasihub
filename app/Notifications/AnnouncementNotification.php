<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Models\EmailTemplate;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AnnouncementNotification extends Notification
{

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
        $announcementUrl = $this->announcement->audience->value === 'public'
            ? route('public.announcements.show', $this->announcement->slug)
            : route('member.announcements.show', $this->announcement->slug);

        $template = EmailTemplate::render('announcement', [
            'title' => $this->announcement->title,
            'summary' => $this->announcement->summary ?? strip_tags((string) $this->announcement->content),
            'content' => strip_tags((string) $this->announcement->content),
            'action_url' => $announcementUrl,
            'cooperative_name' => $this->announcement->cooperative?->name ?? 'Koperasi',
        ]);

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->action('Lihat Pengumuman', $announcementUrl)
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
        }

        return (new MailMessage)
            ->subject($this->announcement->title)
            ->greeting('Salam sejahtera,')
            ->line($this->announcement->summary ?? strip_tags((string) $this->announcement->content))
            ->action('Lihat Pengumuman', $announcementUrl)
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
                : route('member.announcements.show', $this->announcement->slug),
        ];
    }
}