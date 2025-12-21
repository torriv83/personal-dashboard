<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class WishlistImagesFetched extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $totalProcessed,
        public int $imagesFound
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
        $body = $this->imagesFound > 0
            ? "Fant {$this->imagesFound} av {$this->totalProcessed} bilder"
            : 'Ingen nye bilder funnet';

        return (new WebPushMessage)
            ->title('Bildehenting fullført')
            ->icon('/icons/icon-192x192.png')
            ->badge('/icons/icon-72x72.png')
            ->body($body)
            ->tag('wishlist-images-fetched')
            ->data(['url' => '/onskeliste']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'total_processed' => $this->totalProcessed,
            'images_found' => $this->imagesFound,
        ];
    }
}
