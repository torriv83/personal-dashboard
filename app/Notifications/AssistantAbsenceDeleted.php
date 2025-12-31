<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Assistant;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class AssistantAbsenceDeleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Assistant $assistant,
        public Carbon $startsAt,
        public Carbon $endsAt,
        public ?string $note = null
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
        $assistantName = $this->assistant->name;
        $startDate = $this->startsAt->translatedFormat('j. M');
        $endDate = $this->endsAt->translatedFormat('j. M');

        if ($this->startsAt->isSameDay($this->endsAt)) {
            $dateText = $startDate;
        } else {
            $dateText = "{$startDate} - {$endDate}";
        }

        $body = "{$assistantName} slettet fravær: {$dateText}";

        if ($this->note) {
            $body .= " ({$this->note})";
        }

        return (new WebPushMessage)
            ->title('Fravær slettet')
            ->icon('/icons/icon-192x192.png')
            ->badge('/icons/icon-72x72.png')
            ->body($body)
            ->data(['url' => '/bpa/kalender']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'assistant_id' => $this->assistant->id,
            'starts_at' => $this->startsAt->toIso8601String(),
            'ends_at' => $this->endsAt->toIso8601String(),
        ];
    }
}
