<?php

namespace App\Providers;

use App\Services\LoggingPushReportHandler;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\WebPush\ReportHandlerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ReportHandlerInterface::class, LoggingPushReportHandler::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
