<?php

namespace App\Notifications;

use App\Models\FinancingGuarantor;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinancingGuarantorAccepted extends Notification
{
    public function __construct(
        private readonly FinancingGuarantor $guarantor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $guarantorName = $this->guarantor->guarantorMember?->full_name ?? 'Penjamin';

        return [
            'id' => $this->guarantor->id,
            'title' => 'Penjamin Mengesahkan',
            'message' => $guarantorName.' telah bersetuju menjadi penjamin untuk permohonan pembiayaan anda.',
            'url' => route('member.financing.applications.show', $this->guarantor->financing_application_id),
        ];
    }
}