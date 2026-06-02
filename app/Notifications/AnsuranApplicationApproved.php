<?php

namespace App\Notifications;

use App\Models\AnsuranApplication;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnsuranApplicationApproved extends Notification implements ShouldQueue
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
        $template = EmailTemplate::render('ansuran_application_approved', $this->templateData());

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->action('Semak Status', route('member.ansuran.applications.show', $this->application))
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
        }

        return (new MailMessage)
            ->subject('Permohonan Ansuran Mudah Diluluskan')
            ->greeting('Salam sejahtera,')
            ->line('Permohonan Ansuran Mudah anda telah diluluskan.')
            ->line('No Permohonan: '.$this->application->application_no)
            ->line('Produk: '.$this->application->product->name.' - '.$this->application->variant->name)
            ->line('Bayaran Bulanan: RM '.number_format($this->application->monthly_amount, 2))
            ->line('Tempoh: '.$this->application->tenure_months.' Bulan')
            ->action('Semak Status', route('member.ansuran.applications.show', $this->application))
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'application_no' => $this->application->application_no,
            'title' => 'Permohonan Diluluskan',
            'message' => 'Permohonan '.$this->application->application_no.' telah diluluskan',
            'url' => route('member.ansuran.applications.show', $this->application),
        ];
    }

    private function templateData(): array
    {
        return [
            'application_no' => $this->application->application_no,
            'product_name' => $this->application->product->name,
            'variant_name' => $this->application->variant->name,
            'monthly_amount' => number_format($this->application->monthly_amount, 2),
            'tenure_months' => (string) $this->application->tenure_months,
            'cooperative_name' => $this->application->member->cooperative?->name ?? 'Koperasi',
        ];
    }
}