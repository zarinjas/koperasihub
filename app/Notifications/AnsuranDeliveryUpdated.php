<?php

namespace App\Notifications;

use App\Models\AnsuranApplication;
use App\Enums\AnsuranDeliveryStatus;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnsuranDeliveryUpdated extends Notification implements ShouldQueue
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
        $template = EmailTemplate::render('ansuran_delivery_updated', $this->templateData());

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->action('Semak Status', route('member.ansuran.applications.show', $this->application))
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
        }

        $deliveryLabel = AnsuranDeliveryStatus::tryFrom($this->application->delivery_status)?->label() ?? $this->application->delivery_status;

        return (new MailMessage)
            ->subject('Status Penghantaran Ansuran Mudah Dikemaskini')
            ->greeting('Salam sejahtera,')
            ->line('Status penghantaran pesanan anda telah dikemaskini.')
            ->line('No Permohonan: '.$this->application->application_no)
            ->line('Produk: '.$this->application->product->name)
            ->line('Status: '.$deliveryLabel)
            ->when($this->application->delivery_tracking_no, function ($mail) {
                return $mail->line('No Tracking: '.$this->application->delivery_tracking_no);
            })
            ->action('Semak Status', route('member.ansuran.applications.show', $this->application))
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'application_no' => $this->application->application_no,
            'title' => 'Status Penghantaran Dikemaskini',
            'message' => 'Status penghantaran '.$this->application->application_no.' telah dikemaskini',
            'url' => route('member.ansuran.applications.show', $this->application),
        ];
    }

    private function templateData(): array
    {
        $deliveryLabel = AnsuranDeliveryStatus::tryFrom($this->application->delivery_status)?->label() ?? $this->application->delivery_status;

        return [
            'application_no' => $this->application->application_no,
            'product_name' => $this->application->product->name,
            'delivery_status' => $deliveryLabel,
            'tracking_no' => $this->application->delivery_tracking_no ?? '',
            'cooperative_name' => $this->application->member->cooperative?->name ?? 'Koperasi',
        ];
    }
}