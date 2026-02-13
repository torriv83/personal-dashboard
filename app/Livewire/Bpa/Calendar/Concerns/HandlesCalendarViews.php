<?php

declare(strict_types=1);

namespace App\Livewire\Bpa\Calendar\Concerns;

use Carbon\Carbon;

/**
 * @property int $currentWeekNumber
 */
trait HandlesCalendarViews
{
    public function getCalendarDaysProperty(): array
    {
        $firstOfMonth = Carbon::create($this->year, $this->month, 1);
        $lastOfMonth = $firstOfMonth->copy()->endOfMonth();

        // Get the Monday of the week the month starts
        $startDate = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);

        // Get the Sunday of the week the month ends
        $endDate = $lastOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        $current = $startDate->copy();
        $today = Carbon::now('Europe/Oslo')->format('Y-m-d');

        while ($current <= $endDate) {
            $days[] = [
                'date' => $current->format('Y-m-d'),
                'day' => $current->day,
                'isCurrentMonth' => $current->month === $this->month,
                'isToday' => $current->format('Y-m-d') === $today,
                'isWeekend' => $current->isWeekend(),
                'weekNumber' => $current->isoWeek(),
                'dayOfWeek' => $current->dayOfWeekIso, // 1 = Monday, 7 = Sunday
            ];
            $current->addDay();
        }

        return $days;
    }

    public function getWeeksProperty(): array
    {
        return array_chunk($this->getCalendarDaysProperty(), 7);
    }

    public function getCurrentMonthNameProperty(): string
    {
        return $this->norwegianMonths[$this->month];
    }

    public function getCurrentDateProperty(): Carbon
    {
        return Carbon::create($this->year, $this->month, $this->day, 0, 0, 0, 'Europe/Oslo');
    }

    public function getFormattedDateProperty(): string
    {
        $date = $this->getCurrentDateProperty();
        $dayName = $this->norwegianDaysFull[$date->dayOfWeekIso - 1];

        return $dayName . ', ' . $date->day . '. ' . $this->norwegianMonths[$date->month] . ' ' . $date->year;
    }

    public function getTimeSlotsProperty(): array
    {
        $slots = [];
        $startHour = 8;
        $endHour = 23;

        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            $slots[] = [
                'hour' => $hour,
                'label' => sprintf('%02d:00', $hour),
                'isCurrentHour' => Carbon::now('Europe/Oslo')->hour === $hour,
            ];
        }

        return $slots;
    }

    public function getCurrentTimePositionProperty(): ?float
    {
        $now = Carbon::now('Europe/Oslo');
        $hour = $now->hour;
        $minute = $now->minute;

        // Only show if within visible hours (8-23)
        if ($hour < 8 || $hour > 23) {
            return null;
        }

        // Calculate position as percentage from 08:00
        $minutesFrom8 = ($hour - 8) * 60 + $minute;
        $totalMinutes = 16 * 60; // 16 hours (08:00 to 23:00 inclusive = 16 slots)

        return ($minutesFrom8 / $totalMinutes) * 100;
    }

    public function getIsTodaySelectedProperty(): bool
    {
        $today = Carbon::now('Europe/Oslo');

        return $this->year === $today->year
            && $this->month === $today->month
            && $this->day === $today->day;
    }

    public function getCurrentWeekDaysProperty(): array
    {
        $currentDate = Carbon::create($this->year, $this->month, $this->day);
        $startOfWeek = $currentDate->copy()->startOfWeek(Carbon::MONDAY);
        $today = Carbon::now('Europe/Oslo')->format('Y-m-d');

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->day,
                'dayName' => $this->norwegianDays[$i],
                'dayNameFull' => $this->norwegianDaysFull[$i],
                'isToday' => $date->format('Y-m-d') === $today,
                'isWeekend' => $date->isWeekend(),
                'isSelected' => $date->day === $this->day && $date->month === $this->month,
            ];
        }

        return $days;
    }

    public function getWeekRangeProperty(): string
    {
        $currentDate = Carbon::create($this->year, $this->month, $this->day);
        $startOfWeek = $currentDate->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $currentDate->copy()->endOfWeek(Carbon::SUNDAY);

        if ($startOfWeek->month === $endOfWeek->month) {
            return $startOfWeek->day . ' - ' . $endOfWeek->day . '. ' . $this->norwegianMonths[$startOfWeek->month] . ' ' . $startOfWeek->year;
        }

        return $startOfWeek->day . '. ' . $this->norwegianMonths[$startOfWeek->month] . ' - ' . $endOfWeek->day . '. ' . $this->norwegianMonths[$endOfWeek->month] . ' ' . $endOfWeek->year;
    }

    public function getWeekRangeShortProperty(): string
    {
        $currentDate = Carbon::create($this->year, $this->month, $this->day);
        $startOfWeek = $currentDate->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $currentDate->copy()->endOfWeek(Carbon::SUNDAY);

        $shortMonths = [
            1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
            5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'aug',
            9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'des',
        ];

        if ($startOfWeek->month === $endOfWeek->month) {
            return $startOfWeek->day . '-' . $endOfWeek->day . '. ' . $shortMonths[$startOfWeek->month];
        }

        return $startOfWeek->day . '. ' . $shortMonths[$startOfWeek->month] . ' - ' . $endOfWeek->day . '. ' . $shortMonths[$endOfWeek->month];
    }

    public function getCurrentWeekNumberProperty(): int
    {
        return Carbon::create($this->year, $this->month, $this->day)->isoWeek();
    }

    public function getWeeksInMonthProperty(): array
    {
        $firstOfMonth = Carbon::create($this->year, $this->month, 1);
        $lastOfMonth = $firstOfMonth->copy()->endOfMonth();

        $shortMonths = [
            1 => 'jan', 2 => 'feb', 3 => 'mar', 4 => 'apr',
            5 => 'mai', 6 => 'jun', 7 => 'jul', 8 => 'aug',
            9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'des',
        ];

        $weeks = [];
        $currentWeek = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $selectedWeekNumber = $this->currentWeekNumber;

        while ($currentWeek->lte($lastOfMonth)) {
            $endOfWeek = $currentWeek->copy()->endOfWeek(Carbon::SUNDAY);
            $weekNumber = $currentWeek->isoWeek();

            if ($currentWeek->month === $endOfWeek->month) {
                $label = $currentWeek->day . '-' . $endOfWeek->day . '. ' . $shortMonths[$currentWeek->month];
            } else {
                $label = $currentWeek->day . '. ' . $shortMonths[$currentWeek->month] . ' - ' . $endOfWeek->day . '. ' . $shortMonths[$endOfWeek->month];
            }

            $weeks[] = [
                'weekNumber' => $weekNumber,
                'label' => 'Uke ' . $weekNumber . ': ' . $label,
                'labelShort' => $label,
                'date' => $currentWeek->format('Y-m-d'),
                'isSelected' => $weekNumber === $selectedWeekNumber,
            ];

            $currentWeek->addWeek();
        }

        return $weeks;
    }

    /**
     * Get the start date for the visible range based on current view.
     */
    private function getVisibleStartDate(): Carbon
    {
        return match ($this->view) {
            'day' => Carbon::create($this->year, $this->month, $this->day)->startOfDay(),
            'week' => Carbon::create($this->year, $this->month, $this->day)->startOfWeek(Carbon::MONDAY),
            default => Carbon::create($this->year, $this->month, 1)->startOfWeek(Carbon::MONDAY),
        };
    }

    /**
     * Get the end date for the visible range based on current view.
     */
    private function getVisibleEndDate(): Carbon
    {
        return match ($this->view) {
            'day' => Carbon::create($this->year, $this->month, $this->day)->endOfDay(),
            'week' => Carbon::create($this->year, $this->month, $this->day)->endOfWeek(Carbon::SUNDAY),
            default => Carbon::create($this->year, $this->month, 1)->endOfMonth()->endOfWeek(Carbon::SUNDAY),
        };
    }
}
