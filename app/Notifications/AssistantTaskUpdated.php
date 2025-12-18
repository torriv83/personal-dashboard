<?php

namespace App\Notifications;

use App\Models\Assistant;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class AssistantTaskUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public Assistant $assistant,
        public string $action // 'added' or 'completed'
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
        $taskTitle = $this->task->title;

        if ($this->action === 'completed') {
            $title = 'Oppgave fullført';
            $body = "{$assistantName} fullførte: {$taskTitle}";
            $icon = '✅';
        } else {
            $title = 'Ny oppgave lagt til';
            $body = "{$assistantName} la til: {$taskTitle}";
            $icon = '➕';
        }

        return (new WebPushMessage)
            ->title($title)
            ->icon('/icons/icon-192x192.png')
            ->badge('/icons/icon-72x72.png')
            ->body($body)
            ->tag("task-{$this->action}-{$this->task->id}")
            ->data(['url' => '/oppgaver']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'assistant_id' => $this->assistant->id,
            'action' => $this->action,
        ];
    }
}
