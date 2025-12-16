<?php

namespace App\Notifications;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ShiftReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Shift $shift,
        public string $reminderType // 'day_before' | 'hours_before'
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
        $title = $this->reminderType === 'day_before'
            ? 'Vakt i morgen'
            : 'Kommende vakt';

        $time = $this->shift->starts_at->format('H:i');
        $date = $this->shift->starts_at->translatedFormat('l d. F');
        $assistant = $this->shift->assistant->name ?? 'Ukjent assistent';

        return (new WebPushMessage)
            ->title($title)
            ->icon('/icons/icon-192x192.png')
            ->badge('/icons/icon-72x72.png')
            ->body("{$assistant} - {$date} kl. {$time}")
            ->tag("shift-{$this->shift->id}-{$this->reminderType}")
            ->data(['url' => '/bpa/kalender']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'shift_id' => $this->shift->id,
            'reminder_type' => $this->reminderType,
            'starts_at' => $this->shift->starts_at->toIso8601String(),
        ];
    }
}
