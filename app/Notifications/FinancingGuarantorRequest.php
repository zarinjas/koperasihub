<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\FinancingApplication;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinancingGuarantorRequest extends Notification
{

    public function __construct(
        private readonly FinancingApplication $application,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::render('financing_guarantor_request', $this->templateData());

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->action('Semak Permintaan', route('member.financing.guarantor-requests.index'))
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
        }

        $applicantName = $this->application->member?->user?->name ?? $this->application->member?->full_name ?? 'Pemohon';
        $productName = $this->application->product?->name ?? 'Produk';

        return (new MailMessage)
            ->subject('Permintaan Menjadi Penjamin Pembiayaan')
            ->greeting('Salam sejahtera,')
            ->line('Anda telah dipilih sebagai penjamin untuk permohonan pembiayaan.')
            ->line('Pemohon: '.$applicantName)
            ->line('Produk: '.$productName)
            ->line('Jumlah: RM '.number_format((float) $this->application->amount_requested, 2))
            ->action('Semak Permintaan', route('member.financing.guarantor-requests.index'))
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
    }

    public function toDatabase(object $notifiable): array
    {
        $applicantName = $this->application->member?->user?->name ?? $this->application->member?->full_name ?? 'Pemohon';

        return [
            'application_id' => $this->application->id,
            'reference_no' => $this->application->reference_no,
            'title' => 'Permintaan Penjamin Pembiayaan',
            'message' => 'Anda dipilih sebagai penjamin oleh '.$applicantName,
            'url' => route('member.financing.guarantor-requests.index'),
        ];
    }

    private function templateData(): array
    {
        $applicantName = $this->application->member?->user?->name ?? $this->application->member?->full_name ?? 'Pemohon';

        return [
            'reference_no' => $this->application->reference_no,
            'member_name' => $applicantName,
            'product_name' => $this->application->product?->name ?? 'Produk',
            'amount' => number_format((float) $this->application->amount_requested, 2),
            'cooperative_name' => $this->application->member?->cooperative?->name ?? 'Koperasi',
        ];
    }
}