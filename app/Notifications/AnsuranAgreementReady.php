<?php

namespace App\Notifications;

use App\Models\AnsuranApplication;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnsuranAgreementReady extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly AnsuranApplication $application,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::render('ansuran_agreement_ready', $this->templateData());

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->action('Tandatangani Perjanjian', route('member.ansuran.applications.sign', $this->application))
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
        }

        return (new MailMessage)
            ->subject('Perjanjian Ansuran Mudah Sedia Ditandatangani')
            ->greeting('Salam sejahtera,')
            ->line('Perjanjian Ansuran Mudah anda telah sedia untuk ditandatangani.')
            ->line('No Permohonan: '.$this->application->application_no)
            ->line('Produk: '.$this->application->product->name)
            ->line('Sila log masuk ke portal ahli untuk menandatangani perjanjian secara digital.')
            ->action('Tandatangani Perjanjian', route('member.ansuran.applications.sign', $this->application))
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'application_no' => $this->application->application_no,
            'title' => 'Perjanjian Sedia Ditandatangani',
            'message' => 'Perjanjian '.$this->application->application_no.' sedia untuk ditandatangani',
            'url' => route('member.ansuran.applications.sign', $this->application),
        ];
    }

    private function templateData(): array
    {
        return [
            'application_no' => $this->application->application_no,
            'product_name' => $this->application->product->name,
            'cooperative_name' => $this->application->member->cooperative?->name ?? 'Koperasi',
        ];
    }
}
