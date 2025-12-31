<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Archive past absence entries daily at midnight
Schedule::command('shifts:archive-past-absences')->daily();

// Send monthly assistant reports on the last day of each month at 18:00
Schedule::command('assistants:send-monthly-reports')->lastDayOfMonth('18:00');

// Push notification commands
Schedule::command('notifications:prescription-alerts')->hourly();
Schedule::command('notifications:shift-reminders')->everyFiveMinutes();
