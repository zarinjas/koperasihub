<?php

namespace App\Notifications;

use App\Models\AnsuranApplication;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnsuranApplicationSubmitted extends Notification implements ShouldQueue
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
        $template = EmailTemplate::render('ansuran_application_submitted', $this->templateData());

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->action('Semak Permohonan', route('admin.ansuran.applications.show', $this->application))
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
        }

        return (new MailMessage)
            ->subject('Permohonan Ansuran Mudah Baru: '.$this->application->application_no)
            ->greeting('Salam sejahtera,')
            ->line('Permohonan Ansuran Mudah baharu telah diterima.')
            ->line('No Permohonan: '.$this->application->application_no)
            ->line('Ahli: '.$this->application->member->user->name)
            ->line('Produk: '.$this->application->product->name)
            ->line('Jumlah: RM '.number_format($this->application->full_price, 2))
            ->action('Semak Permohonan', route('admin.ansuran.applications.show', $this->application))
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'application_no' => $this->application->application_no,
            'title' => 'Permohonan Ansuran Mudah Baru',
            'message' => 'Permohonan baharu daripada '.$this->application->member->user->name,
            'url' => route('admin.ansuran.applications.show', $this->application),
        ];
    }

    private function templateData(): array
    {
        return [
            'application_no' => $this->application->application_no,
            'member_name' => $this->application->member->user->name,
            'product_name' => $this->application->product->name,
            'amount' => number_format($this->application->full_price, 2),
            'cooperative_name' => $this->application->member->cooperative?->name ?? 'Koperasi',
        ];
    }
}
