<?php

namespace App\Notifications;

use App\Models\AnsuranApplication;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnsuranGuarantorRequest extends Notification implements ShouldQueue
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
        $template = EmailTemplate::render('ansuran_guarantor_request', $this->templateData());

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->action('Semak Permintaan', route('member.ansuran.guarantor-requests.index'))
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
        }

        return (new MailMessage)
            ->subject('Permintaan Menjadi Penjamin Ansuran Mudah')
            ->greeting('Salam sejahtera,')
            ->line('Anda telah dipilih sebagai penjamin untuk permohonan Ansuran Mudah.')
            ->line('Ahli: '.$this->application->member->user->name)
            ->line('Produk: '.$this->application->product->name.' - '.$this->application->variant->name)
            ->line('Jumlah: RM '.number_format($this->application->full_price, 2))
            ->action('Semak Permintaan', route('member.ansuran.guarantor-requests.index'))
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'application_no' => $this->application->application_no,
            'title' => 'Permintaan Penjamin Ansuran Mudah',
            'message' => 'Anda dipilih sebagai penjamin oleh '.$this->application->member->user->name,
            'url' => route('member.ansuran.guarantor-requests.index'),
        ];
    }

    private function templateData(): array
    {
        return [
            'member_name' => $this->application->member->user->name,
            'product_name' => $this->application->product->name,
            'variant_name' => $this->application->variant->name,
            'amount' => number_format($this->application->full_price, 2),
            'cooperative_name' => $this->application->member->cooperative?->name ?? 'Koperasi',
        ];
    }
}