<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\MembershipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipApplicationApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly MembershipApplication $application,
        private readonly string $memberNo,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::render('membership_application_approved', [
            'application_no' => $this->application->application_no,
            'full_name' => $this->application->full_name,
            'member_no' => $this->memberNo,
            'cooperative_name' => $this->application->cooperative?->name ?? 'Koperasi',
            'identity_no' => $this->application->identity_no,
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
            ->subject('Permohonan Keahlian Diluluskan: ' . $this->application->application_no)
            ->greeting('Salam sejahtera ' . $this->application->full_name . ',')
            ->line('Tahniah! Permohonan keahlian anda telah diluluskan.')
            ->line('No Permohonan: ' . $this->application->application_no)
            ->line('No Ahli: ' . $this->memberNo)
            ->line('')
            ->line('Langkah seterusnya untuk mengaktifkan akaun portal ahli:')
            ->line('1. Layari portal ahli koperasi')
            ->line('2. Masukkan No. Kad Pengenalan anda: ' . $this->application->identity_no)
            ->line('3. Ikut arahan untuk mengaktifkan akaun dan menetapkan kata laluan')
            ->line('')
            ->line('Selepas diaktifkan, anda boleh log masuk menggunakan No. Ahli, No. IC atau e-mel.')
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem.');
    }
}