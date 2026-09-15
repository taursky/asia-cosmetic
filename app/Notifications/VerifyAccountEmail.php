<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyAccountEmail extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $email,
        private readonly string $verificationUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->replyTo($this->email)
            ->subject('Подтверждение email — Asia Cosmetic')
            ->greeting('Здравствуйте!')
            ->line('Вы запросили добавление этого email к аккаунту Asia Cosmetic.')
            ->action('Подтвердить email', $this->verificationUrl)
            ->line('Ссылка действует 30 минут.')
            ->line('Если вы не запрашивали изменение email, просто проигнорируйте письмо.');
    }
}
