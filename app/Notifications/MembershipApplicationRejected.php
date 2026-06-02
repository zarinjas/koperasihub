<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\MembershipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipApplicationRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly MembershipApplication $application,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::render('membership_application_rejected', [
            'application_no' => $this->application->application_no,
            'full_name' => $this->application->full_name,
            'rejection_reason' => $this->application->rejection_reason ?? '',
            'cooperative_name' => $this->application->cooperative?->name ?? 'Koperasi',
        ]);

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem.');
        }

        return (new MailMessage)
            ->subject('Permohonan Keahlian Ditolak: ' . $this->application->application_no)
            ->greeting('Salam sejahtera ' . $this->application->full_name . ',')
            ->line('Permohonan keahlian anda telah ditolak.')
            ->line('No Permohonan: ' . $this->application->application_no)
            ->line('Sebab: ' . ($this->application->rejection_reason ?? 'Tiada sebab diberikan.'))
            ->line('Sila hubungi pihak koperasi untuk maklumat lanjut.')
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem.');
    }
}