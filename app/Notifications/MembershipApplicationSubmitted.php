<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\MembershipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly MembershipApplication $application,
        private readonly bool $isAdminNotification = true,
    ) {}

    public function via(object $notifiable): array
    {
        if ($this->isAdminNotification) {
            return ['database', 'mail'];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $templateType = $this->isAdminNotification
            ? 'membership_application_submitted_admin'
            : 'membership_application_submitted_applicant';

        $template = EmailTemplate::render($templateType, $this->templateData());

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem.');
        }

        if ($this->isAdminNotification) {
            return (new MailMessage)
                ->subject('Permohonan Keahlian Baru: ' . $this->application->application_no)
                ->greeting('Salam sejahtera,')
                ->line('Permohonan keahlian baharu telah diterima.')
                ->line('No Permohonan: ' . $this->application->application_no)
                ->line('Nama: ' . $this->application->full_name)
                ->line('No. KP: ' . $this->application->identity_no)
                ->line('Emel: ' . $this->application->email)
                ->action('Semak Permohonan', route('admin.membership-applications.show', $this->application))
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem.');
        }

        return (new MailMessage)
            ->subject('Permohonan Keahlian Diterima: ' . $this->application->application_no)
            ->greeting('Salam sejahtera ' . $this->application->full_name . ',')
            ->line('Terima kasih kerana menghantar permohonan keahlian.')
            ->line('No Permohonan: ' . $this->application->application_no)
            ->line('Permohonan anda akan diproses dalam tempoh 3 hari bekerja.')
            ->line('Anda akan dimaklumkan melalui e-mel setelah permohonan diluluskan atau jika terdapat sebarang maklumat tambahan diperlukan.')
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'application_no' => $this->application->application_no,
            'title' => 'Permohonan Keahlian Baru',
            'message' => 'Permohonan baharu daripada ' . $this->application->full_name,
            'url' => route('admin.membership-applications.show', $this->application),
        ];
    }

    private function templateData(): array
    {
        return [
            'application_no' => $this->application->application_no,
            'full_name' => $this->application->full_name,
            'identity_no' => $this->application->identity_no,
            'email' => $this->application->email,
            'phone' => $this->application->phone,
            'cooperative_name' => $this->application->cooperative?->name ?? 'Koperasi',
        ];
    }
}