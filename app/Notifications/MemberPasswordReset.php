<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberPasswordReset extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly string $email,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url(route('member.password.reset', [
            'token' => $this->token,
            'email' => $this->email,
        ], false));

        return (new MailMessage)
            ->subject('Tetapan Semula Kata Laluan Portal Ahli')
            ->greeting('Salam sejahtera,')
            ->line('Anda menerima e-mel ini kerana kami menerima permintaan tetapan semula kata laluan untuk akaun portal ahli anda.')
            ->action('Tetapkan Semula Kata Laluan', $resetUrl)
            ->line('Pautan ini akan tamat tempoh dalam masa 60 minit.')
            ->line('Jika anda tidak membuat permintaan ini, sila abaikan e-mel ini.')
            ->salutation('Sekian, terima kasih.');
    }
}
