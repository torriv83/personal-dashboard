<?php

declare(strict_types=1);

namespace App\Livewire\Bpa\Calendar\Concerns;

use Carbon\Carbon;

trait HandlesCalendarNavigation
{
    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function goToToday(): void
    {
        $now = Carbon::now('Europe/Oslo');
        $this->year = $now->year;
        $this->month = $now->month;
        $this->day = $now->day;
    }

    public function previousDay(): void
    {
        $date = Carbon::create($this->year, $this->month, $this->day)->subDay();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->day = $date->day;
    }

    public function nextDay(): void
    {
        $date = Carbon::create($this->year, $this->month, $this->day)->addDay();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->day = $date->day;
    }

    public function previousWeek(): void
    {
        $date = Carbon::create($this->year, $this->month, $this->day)->subWeek();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->day = $date->day;
    }

    public function nextWeek(): void
    {
        $date = Carbon::create($this->year, $this->month, $this->day)->addWeek();
        $this->year = $date->year;
        $this->month = $date->month;
        $this->day = $date->day;
    }

    public function goToDay(string $date): void
    {
        $carbon = Carbon::parse($date);
        $this->year = $carbon->year;
        $this->month = $carbon->month;
        $this->day = $carbon->day;
        $this->view = 'day';
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function goToMonth(int $month): void
    {
        $this->month = $month;
    }

    public function goToYear(int $year): void
    {
        $this->year = $year;
    }
}
