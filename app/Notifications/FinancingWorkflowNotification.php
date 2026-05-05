<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinancingWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $subjectLine,
        private readonly string $introLine,
        private readonly array $summaryLines,
        private readonly ?string $actionUrl,
        private readonly ?string $actionLabel,
        private readonly ?string $cooperativeName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subjectLine)
            ->greeting('Salam sejahtera,')
            ->line($this->introLine);

        if ($this->cooperativeName) {
            $mail->line('Koperasi: '.$this->cooperativeName);
        }

        foreach ($this->summaryLines as $line) {
            $mail->line($line);
        }

        if ($this->actionUrl && $this->actionLabel) {
            $mail->action($this->actionLabel, $this->actionUrl);
        }

        return $mail->line('E-mel ini dijana secara automatik oleh sistem KoperasiHub.');
    }
}
