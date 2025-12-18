<?php

namespace App\Notifications;

use App\Models\Assistant;
use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class AssistantAbsenceRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Shift $absence,
        public Assistant $assistant
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
        $startDate = $this->absence->starts_at->translatedFormat('j. M');
        $endDate = $this->absence->ends_at->translatedFormat('j. M');

        if ($this->absence->starts_at->isSameDay($this->absence->ends_at)) {
            $dateText = $startDate;
        } else {
            $dateText = "{$startDate} - {$endDate}";
        }

        $body = "{$assistantName} har registrert fravær: {$dateText}";

        if ($this->absence->note) {
            $body .= " ({$this->absence->note})";
        }

        return (new WebPushMessage)
            ->title('Nytt fravær registrert')
            ->icon('/icons/icon-192x192.png')
            ->badge('/icons/icon-72x72.png')
            ->body($body)
            ->tag("absence-{$this->absence->id}")
            ->data(['url' => '/bpa/kalender']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'absence_id' => $this->absence->id,
            'assistant_id' => $this->assistant->id,
            'starts_at' => $this->absence->starts_at->toIso8601String(),
            'ends_at' => $this->absence->ends_at->toIso8601String(),
        ];
    }
}
