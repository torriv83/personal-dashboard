<?php

namespace App\Services;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\MessageSentReport;
use NotificationChannels\WebPush\Events\NotificationFailed;
use NotificationChannels\WebPush\Events\NotificationSent;
use NotificationChannels\WebPush\PushSubscription;
use NotificationChannels\WebPush\ReportHandlerInterface;
use NotificationChannels\WebPush\WebPushMessageInterface;

class LoggingPushReportHandler implements ReportHandlerInterface
{
    public function __construct(protected Dispatcher $events) {}

    public function handleReport(MessageSentReport $report, PushSubscription $subscription, WebPushMessageInterface $message): void
    {
        if ($report->isSuccess()) {
            $this->events->dispatch(new NotificationSent($report, $subscription, $message));

            return;
        }

        // Log failures
        $statusCode = $report->getResponse()?->getStatusCode();

        if ($report->isSubscriptionExpired()) {
            Log::error('Push subscription deleted (expired)', [
                'status_code' => $statusCode,
                'endpoint' => $subscription->endpoint,
                'user_id' => $subscription->subscribable_id,
                'created_at' => $subscription->created_at?->toDateTimeString(),
            ]);

            $subscription->delete();
        } else {
            Log::warning('Push notification failed', [
                'status_code' => $statusCode,
                'reason' => $report->getReason(),
            ]);
        }

        $this->events->dispatch(new NotificationFailed($report, $subscription, $message));
    }
}
