<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\FinancingApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinancingApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly FinancingApplication $application,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::render('financing_application_submitted', $this->templateData());

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->action('Semak Permohonan', route('admin.financing.applications.show', $this->application))
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
        }

        return (new MailMessage)
            ->subject('Permohonan Pembiayaan Baru: ' . $this->application->reference_no)
            ->greeting('Salam sejahtera,')
            ->line('Permohonan pembiayaan baharu telah diterima.')
            ->line('No Rujukan: ' . $this->application->reference_no)
            ->line('Ahli: ' . $this->application->member->user->name)
            ->line('Produk: ' . $this->application->product->name)
            ->line('Jumlah: RM ' . number_format((float) $this->application->amount_requested, 2))
            ->action('Semak Permohonan', route('admin.financing.applications.show', $this->application))
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'reference_no' => $this->application->reference_no,
            'title' => 'Permohonan Pembiayaan Baru',
            'message' => 'Permohonan baharu daripada ' . $this->application->member->user->name,
            'url' => route('admin.financing.applications.show', $this->application),
        ];
    }

    private function templateData(): array
    {
        return [
            'reference_no' => $this->application->reference_no,
            'member_name' => $this->application->member->user->name,
            'product_name' => $this->application->product->name,
            'amount' => number_format((float) $this->application->amount_requested, 2),
            'cooperative_name' => $this->application->member->cooperative?->name ?? 'Koperasi',
        ];
    }
}
