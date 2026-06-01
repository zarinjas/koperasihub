<?php

namespace App\Notifications;

use App\Models\AnsuranApplication;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnsuranAgreementSigned extends Notification implements ShouldQueue
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
        $template = EmailTemplate::render('ansuran_agreement_signed', $this->templateData());

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->action('Urus Penghantaran', route('admin.ansuran.applications.show', $this->application))
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
        }

        return (new MailMessage)
            ->subject('Perjanjian Ansuran Mudah Telah Ditandatangani')
            ->greeting('Salam sejahtera,')
            ->line('Ahli telah menandatangani perjanjian Ansuran Mudah.')
            ->line('No Permohonan: '.$this->application->application_no)
            ->line('Ahli: '.$this->application->member->user->name)
            ->line('Produk: '.$this->application->product->name)
            ->action('Urus Penghantaran', route('admin.ansuran.applications.show', $this->application))
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'application_no' => $this->application->application_no,
            'title' => 'Perjanjian Ditandatangani',
            'message' => $this->application->member->user->name.' telah menandatangani perjanjian '.$this->application->application_no,
            'url' => route('admin.ansuran.applications.show', $this->application),
        ];
    }

    private function templateData(): array
    {
        return [
            'application_no' => $this->application->application_no,
            'member_name' => $this->application->member->user->name,
            'product_name' => $this->application->product->name,
            'cooperative_name' => $this->application->member->cooperative?->name ?? 'Koperasi',
        ];
    }
}
