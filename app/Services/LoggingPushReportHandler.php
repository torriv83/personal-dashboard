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
        $statusCode = $report->getResponse()?->getStatusCode();

        if ($report->isSuccess()) {
            Log::info('Push notification: Sent successfully', [
                'status_code' => $statusCode,
                'endpoint_prefix' => substr($subscription->endpoint, 0, 50).'...',
                'user_id' => $subscription->subscribable_id,
            ]);

            $this->events->dispatch(new NotificationSent($report, $subscription, $message));

            return;
        }

        // Log failures with full details
        if ($report->isSubscriptionExpired()) {
            Log::error('Push subscription: AUTO-DELETED (expired/invalid)', [
                'status_code' => $statusCode,
                'reason' => $report->getReason(),
                'endpoint_prefix' => substr($subscription->endpoint, 0, 50).'...',
                'user_id' => $subscription->subscribable_id,
                'subscription_created_at' => $subscription->getAttribute('created_at')?->toDateTimeString(),
                'action' => 'SUBSCRIPTION_DELETED',
            ]);

            $subscription->delete();
        } else {
            Log::warning('Push notification: Failed to send', [
                'status_code' => $statusCode,
                'reason' => $report->getReason(),
                'endpoint_prefix' => substr($subscription->endpoint, 0, 50).'...',
                'user_id' => $subscription->subscribable_id,
            ]);
        }

        $this->events->dispatch(new NotificationFailed($report, $subscription, $message));
    }
}
