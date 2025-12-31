<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

trait FormatsMinutes
{
    /**
     * Format minutes as HH:MM string for display.
     * Handles negative values with a minus sign prefix.
     */
    protected function formatMinutesForDisplay(int|float|null $minutes): string
    {
        $minutes = (int) ($minutes ?? 0);
        $hours = intdiv(abs($minutes), 60);
        $mins = abs($minutes) % 60;
        $sign = $minutes < 0 ? '-' : '';

        return sprintf('%s%d:%02d', $sign, $hours, $mins);
    }
}
