<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class MetadataFetched extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $totalProcessed,
        public int $updated,
        public int $failed
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
        if ($this->updated === 0 && $this->failed === 0) {
            $body = "Sjekket {$this->totalProcessed} bokmerker - ingen manglet info.";
        } elseif ($this->failed === 0) {
            $body = "Oppdaterte {$this->updated} av {$this->totalProcessed} bokmerker med ny info.";
        } else {
            $body = "Oppdaterte {$this->updated} av {$this->totalProcessed} bokmerker ({$this->failed} feilet).";
        }

        return (new WebPushMessage)
            ->title('Metadata hentet')
            ->icon('/icons/icon-192x192.png')
            ->badge('/icons/icon-72x72.png')
            ->body($body)
            ->tag('metadata-fetched')
            ->data(['url' => '/bokmerker']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'total_processed' => $this->totalProcessed,
            'updated' => $this->updated,
            'failed' => $this->failed,
        ];
    }
}
