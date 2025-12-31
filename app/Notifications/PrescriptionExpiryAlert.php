<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PrescriptionExpiryAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Prescription $prescription,
        public int $daysLeft
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
        $title = match (true) {
            $this->daysLeft < 0 => 'Resept har utgått!',
            $this->daysLeft === 0 => 'Resept utløper i dag!',
            $this->daysLeft === 1 => 'Resept utløper i morgen!',
            default => "Resept utløper om {$this->daysLeft} dager",
        };

        return (new WebPushMessage)
            ->title($title)
            ->icon('/icons/icon-192x192.png')
            ->badge('/icons/icon-72x72.png')
            ->body($this->prescription->name)
            ->tag("prescription-{$this->prescription->id}-{$this->daysLeft}")
            ->data(['url' => '/medisinsk/resepter']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'prescription_id' => $this->prescription->id,
            'prescription_name' => $this->prescription->name,
            'days_left' => $this->daysLeft,
        ];
    }
}
