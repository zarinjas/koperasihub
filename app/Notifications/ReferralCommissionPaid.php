<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\ReferralCommission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralCommissionPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ReferralCommission $commission,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::render('referral_commission_paid', $this->templateData());

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Salam sejahtera,')
                ->line($template['body'])
                ->action('Lihat Rujukan Saya', route('member.referrals.index'))
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
        }

        $amount = number_format($this->commission->commission_amount, 2);
        $referredName = $this->commission->referredMember->full_name;

        return (new MailMessage)
            ->subject('Komisyen Rujukan Telah Dibayar')
            ->greeting('Salam sejahtera,')
            ->line("Komisyen rujukan sebanyak RM{$amount} kerana memperkenalkan {$referredName} telah dibayar ke akaun bank anda.")
            ->action('Lihat Rujukan Saya', route('member.referrals.index'))
            ->salutation('Terima kasih.')
            ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'commission_id' => $this->commission->id,
            'commission_amount' => $this->commission->commission_amount,
            'referred_name' => $this->commission->referredMember->full_name,
            'title' => 'Komisyen Rujukan Telah Dibayar',
            'summary' => 'Komisyen rujukan anda telah dibayar ke akaun bank.',
            'url' => route('member.referrals.index'),
        ];
    }

    private function templateData(): array
    {
        return [
            'amount' => number_format($this->commission->commission_amount, 2),
            'referred_name' => $this->commission->referredMember->full_name,
            'cooperative_name' => $this->commission->referredMember->cooperative?->name ?? 'Koperasi',
        ];
    }
}