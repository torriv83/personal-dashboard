<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\ShiftReminder;
use Illuminate\Console\Command;

class SendShiftReminders extends Command
{
    protected $signature = 'notifications:shift-reminders';

    protected $description = 'Send push-varsler for kommende vakter';

    public function handle(): int
    {
        if (! Setting::get('push_shift_enabled', false)) {
            $this->info('Vakt-paminnelser er deaktivert.');

            return self::SUCCESS;
        }

        $user = User::first();
        if (! $user || ! $user->pushSubscriptions()->exists()) {
            $this->info('Ingen aktive push-abonnementer.');

            return self::SUCCESS;
        }

        $sentCount = 0;

        // Day before reminders (match shift start time)
        if (Setting::get('push_shift_day_before', false)) {
            $sentCount += $this->sendDayBeforeReminders($user);
        }

        // Hours before reminders
        $hoursBefore = Setting::get('push_shift_hours_before');
        if ($hoursBefore) {
            $sentCount += $this->sendHoursBeforeReminders($user, (int) $hoursBefore);
        }

        $this->info("Ferdig. Sendte {$sentCount} varsler.");

        return self::SUCCESS;
    }

    private function sendDayBeforeReminders(User $user): int
    {
        // Find shifts starting tomorrow at current time (+/- 2 min window)
        $tomorrow = now()->addDay();
        $windowStart = $tomorrow->copy()->subMinutes(2);
        $windowEnd = $tomorrow->copy()->addMinutes(3);

        $shifts = Shift::query()
            ->worked()
            ->whereBetween('starts_at', [$windowStart, $windowEnd])
            ->get();

        foreach ($shifts as $shift) {
            $user->notify(new ShiftReminder($shift, 'day_before'));
            $this->info("Sendt dag-for-varsel for vakt: {$shift->starts_at->format('d.m.Y H:i')}");
        }

        return $shifts->count();
    }

    private function sendHoursBeforeReminders(User $user, int $hours): int
    {
        // Find shifts starting in X hours (+/- 2 min window)
        $targetTime = now()->addHours($hours);
        $windowStart = $targetTime->copy()->subMinutes(2);
        $windowEnd = $targetTime->copy()->addMinutes(3);

        $shifts = Shift::query()
            ->worked()
            ->whereBetween('starts_at', [$windowStart, $windowEnd])
            ->get();

        foreach ($shifts as $shift) {
            $user->notify(new ShiftReminder($shift, 'hours_before'));
            $this->info("Sendt timer-for-varsel for vakt: {$shift->starts_at->format('d.m.Y H:i')}");
        }

        return $shifts->count();
    }
}
