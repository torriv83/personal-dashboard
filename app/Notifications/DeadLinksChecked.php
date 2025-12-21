<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class DeadLinksChecked extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $totalChecked,
        public int $deadFound,
        public int $newlyDead,
        public int $revived
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        if ($this->deadFound === 0) {
            $body = "Sjekket {$this->totalChecked} bokmerker - alle lenker fungerer!";
        } elseif ($this->newlyDead > 0) {
            $body = "Fant {$this->newlyDead} nye døde lenker (totalt {$this->deadFound} døde)";
        } else {
            $body = "Sjekket {$this->totalChecked} bokmerker - {$this->deadFound} døde lenker";
        }

        if ($this->revived > 0) {
            $body .= " ({$this->revived} gjenopplivet)";
        }

        return (new WebPushMessage)
            ->title('Døde lenker sjekket')
            ->icon('/icons/icon-192x192.png')
            ->badge('/icons/icon-72x72.png')
            ->body($body)
            ->tag('dead-links-checked')
            ->data(['url' => '/bokmerker']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'total_checked' => $this->totalChecked,
            'dead_found' => $this->deadFound,
            'newly_dead' => $this->newlyDead,
            'revived' => $this->revived,
        ];
    }
}
