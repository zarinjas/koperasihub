<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\ReferralCommission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralCommissionEarned extends Notification implements ShouldQueue
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
        $template = EmailTemplate::render('referral_commission_earned', $this->templateData());

        if ($template) {
            return (new MailMessage)
                ->subject($template['subject'])
                ->greeting('Tahniah!')
                ->line($template['body'])
                ->action('Lihat Rujukan Saya', route('member.referrals.index'))
                ->salutation('Terima kasih.')
                ->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
        }

        $amount = number_format($this->commission->commission_amount, 2);
        $referredName = $this->commission->referredMember->full_name;

        return (new MailMessage)
            ->subject('Komisyen Rujukan Diterima')
            ->greeting('Tahniah!')
            ->line("Anda telah menerima komisyen rujukan sebanyak RM{$amount} kerana memperkenalkan {$referredName}.")
            ->line('Pihak admin akan memproses pembayaran ke akaun bank anda dalam masa terdekat.')
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
            'title' => 'Komisyen Rujukan Diterima',
            'summary' => 'Anda menerima komisyen rujukan kerana memperkenalkan ' . $this->commission->referredMember->full_name . '.',
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
