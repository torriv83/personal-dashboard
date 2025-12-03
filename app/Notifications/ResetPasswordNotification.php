<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Tilbakestill passord - Personal Dashboard')
            ->greeting('Hei '.$notifiable->name.'!')
            ->line('Du mottar denne e-posten fordi vi mottok en forespørsel om å tilbakestille passordet for kontoen din.')
            ->action('Tilbakestill passord', $url)
            ->line('Denne lenken utløper om 60 minutter.')
            ->line('Hvis du ikke ba om å tilbakestille passordet, kan du ignorere denne e-posten.')
            ->salutation('Mvh, Personal Dashboard');
    }
}
