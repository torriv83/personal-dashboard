<?php

namespace App\Providers;

use App\Services\LoggingPushReportHandler;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\WebPush\ReportHandlerInterface;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

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
        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory)->create(
                new Dsn(
                    'brevo+api',
                    'default',
                    config('services.brevo.key')
                )
            );
        });
    }
}
