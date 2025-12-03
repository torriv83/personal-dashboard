<?php

namespace App\Livewire\Bpa;

use App\Models\Assistant;
use App\Models\Setting;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read array $stats
 * @property-read int $weeklyTotalPages
 * @property-read array $weeklyHours
 * @property-read int $shiftsTotalPages
 * @property-read array $upcomingShifts
 * @property-read string $upcomingShiftsTotal
 * @property-read array $unavailableEmployees
 * @property-read int $employeesTotalPages
 * @property-read array $employees
 * @property-read array $monthlyChartData
 * @property-read array $percentageChartData
 */
class Dashboard extends Component
{
    // Pagination state
    public int $weeklyPerPage = 3;

    public int $weeklyPage = 1;

    public int $shiftsPerPage = 10;

    public int $shiftsPage = 1;

    public int $employeesPerPage = 3;

    public int $employeesPage = 1;

    public string $employeesSortDirection = 'desc';

    // Reset page when per-page changes
    public function updatedWeeklyPerPage(): void
    {
        $this->weeklyPage = 1;
        unset($this->weeklyHours);
    }

    public function updatedShiftsPerPage(): void
    {
        $this->shiftsPage = 1;
        unset($this->upcomingShifts);
    }

    public function updatedEmployeesPerPage(): void
    {
        $this->employeesPage = 1;
        unset($this->employees);
    }

    // Navigation methods
    public function nextWeeklyPage(): void
    {
        $this->weeklyPage++;
        unset($this->weeklyHours);
    }

    public function prevWeeklyPage(): void
    {
        if ($this->weeklyPage > 1) {
            $this->weeklyPage--;
            unset($this->weeklyHours);
        }
    }

    public function nextShiftsPage(): void
    {
        $this->shiftsPage++;
        unset($this->upcomingShifts);
    }

    public function prevShiftsPage(): void
    {
        if ($this->shiftsPage > 1) {
            $this->shiftsPage--;
            unset($this->upcomingShifts);
        }
    }

    public function nextEmployeesPage(): void
    {
        $this->employeesPage++;
        unset($this->employees);
    }

    public function prevEmployeesPage(): void
    {
        if ($this->employeesPage > 1) {
            $this->employeesPage--;
            unset($this->employees);
        }
    }

    public function sortEmployeesByHours(): void
    {
        $this->employeesSortDirection = $this->employeesSortDirection === 'desc' ? 'asc' : 'desc';
        $this->employeesPage = 1;
        unset($this->employees);
    }

    #[Computed]
    public function stats(): array
    {
        $now = Carbon::now();
        $currentYear = $now->year;

        // Get BPA settings
        $hoursPerWeek = Setting::getBpaHoursPerWeek();
        $yearlyQuotaMinutes = $hoursPerWeek * 52 * 60;

        // Calculate hours used this year (only past shifts, not future planned ones)
        $usedThisYearMinutes = Shift::query()
            ->worked()
            ->forYear($currentYear)
            ->where('starts_at', '<=', $now)
            ->sum('duration_minutes');

        // Calculate hours used this month (only past shifts)
        $usedThisMonthMinutes = Shift::query()
            ->worked()
            ->forMonth($currentYear, $now->month)
            ->where('starts_at', '<=', $now)
            ->sum('duration_minutes');

        // Calculate hours used this week (only past shifts)
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $usedThisWeekMinutes = Shift::query()
            ->worked()
            ->whereBetween('starts_at', [$startOfWeek, $now])
            ->sum('duration_minutes');

        // Calculate planned hours (future shifts)
        $plannedMinutes = Shift::query()
            ->worked()
            ->where('starts_at', '>', $now)
            ->forYear($currentYear)
            ->sum('duration_minutes');

        // Calculate planned this week
        $plannedThisWeekMinutes = Shift::query()
            ->worked()
            ->where('starts_at', '>', $now)
            ->whereBetween('starts_at', [$startOfWeek, $endOfWeek])
            ->sum('duration_minutes');

        // Calculate remaining
        $remainingMinutes = $yearlyQuotaMinutes - $usedThisYearMinutes;
        $remainingWithPlannedMinutes = $remainingMinutes - $plannedMinutes;

        // Monthly quota
        $monthlyQuotaMinutes = $yearlyQuotaMinutes / 12;

        // Calculate weeks remaining in the year (including current week)
        $endOfYear = $now->copy()->endOfYear();
        $daysRemaining = (int) $now->diffInDays($endOfYear) + 1;
        $weeksRemainingInYear = max(1, (int) ceil($daysRemaining / 7));

        // Average hours remaining per week for the rest of the year
        $averageRemainingPerWeekMinutes = (int) ($remainingMinutes / $weeksRemainingInYear);
        $averageRemainingWithPlannedPerWeekMinutes = (int) ($remainingWithPlannedMinutes / $weeksRemainingInYear);

        return [
            [
                'label' => 'Antall Assistenter',
                'value' => (string) Assistant::count(),
                'description' => null,
            ],
            [
                'label' => 'Timer brukt i år',
                'value' => $this->formatMinutes($usedThisYearMinutes),
                'valueSuffix' => '('.$this->formatMinutes($usedThisYearMinutes + $plannedMinutes).' med planlagt)',
                'description' => $this->formatMinutes($usedThisMonthMinutes).' brukt av '.$this->formatMinutes((int) $monthlyQuotaMinutes).' denne måneden | '.$this->formatMinutes($plannedMinutes).' planlagt ut året',
            ],
            [
                'label' => 'Timer igjen',
                'value' => $this->formatMinutes($remainingMinutes),
                'valueSuffix' => '('.$this->formatMinutes($remainingWithPlannedMinutes).' med planlagt)',
                'description' => $this->formatMinutes($averageRemainingPerWeekMinutes).' snitt/uke ut året | '.$this->formatMinutes($averageRemainingWithPlannedPerWeekMinutes).' snitt/uke med planlagt',
            ],
            [
                'label' => 'Timer brukt denne uka',
                'value' => $this->formatMinutes($usedThisWeekMinutes),
                'valueSuffix' => '('.$this->formatMinutes($usedThisWeekMinutes + $plannedThisWeekMinutes).' med planlagt)',
                'description' => $this->formatMinutes($plannedThisWeekMinutes).' planlagt denne uka',
            ],
        ];
    }

    #[Computed]
    public function weeklyTotalPages(): int
    {
        $now = Carbon::now();

        $weekCount = Shift::query()
            ->worked()
            ->where('starts_at', '>=', $now->copy()->subWeeks(52)->startOfWeek())
            ->where('starts_at', '<=', $now)
            ->get()
            ->groupBy(fn ($shift) => Carbon::parse($shift->starts_at)->weekOfYear)
            ->count();

        return max(1, (int) ceil($weekCount / $this->weeklyPerPage));
    }

    #[Computed]
    public function weeklyHours(): array
    {
        $now = Carbon::now();

        return Shift::query()
            ->worked()
            ->where('starts_at', '>=', $now->copy()->subWeeks(52)->startOfWeek())
            ->where('starts_at', '<=', $now)
            ->get()
            ->groupBy(fn (Shift $shift) => $shift->starts_at->weekOfYear)
            ->map(function (Collection $shifts, int $week) {
                $totalMinutes = $shifts->sum('duration_minutes');
                $count = $shifts->count();
                $averageMinutes = $count > 0 ? (int) ($totalMinutes / $count) : 0;

                return [
                    'week' => $week,
                    'total' => $this->formatMinutes($totalMinutes),
                    'average' => $this->formatMinutes($averageMinutes),
                    'count' => $count,
                ];
            })
            ->sortByDesc('week')
            ->values()
            ->skip(($this->weeklyPage - 1) * $this->weeklyPerPage)
            ->take($this->weeklyPerPage)
            ->values()
            ->toArray();
    }

    #[Computed]
    public function shiftsTotalPages(): int
    {
        $totalCount = Shift::query()
            ->worked()
            ->upcoming()
            ->count();

        return max(1, (int) ceil($totalCount / $this->shiftsPerPage));
    }

    #[Computed]
    public function upcomingShifts(): array
    {
        return Shift::query()
            ->worked()
            ->upcoming()
            ->with('assistant')
            ->orderBy('starts_at')
            ->skip(($this->shiftsPage - 1) * $this->shiftsPerPage)
            ->take($this->shiftsPerPage)
            ->get()
            ->map(fn (Shift $shift) => [
                'name' => $shift->assistant->name ?? 'Ukjent',
                'from' => $shift->starts_at->format('d.m.Y, H:i'),
                'to' => $shift->ends_at->format('d.m.Y, H:i'),
                'duration' => $this->formatMinutes($shift->duration_minutes),
                'duration_minutes' => $shift->duration_minutes,
            ])
            ->toArray();
    }

    #[Computed]
    public function upcomingShiftsTotal(): string
    {
        $shifts = $this->upcomingShifts();
        $totalMinutes = array_sum(array_column($shifts, 'duration_minutes'));

        return $this->formatMinutes($totalMinutes);
    }

    #[Computed]
    public function unavailableEmployees(): array
    {
        $now = Carbon::now();

        return Shift::query()
            ->unavailable()
            ->where('starts_at', '>=', $now->startOfDay())
            ->where('starts_at', '<=', $now->copy()->addDays(14))
            ->with('assistant')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Shift $shift) => [
                'name' => $shift->assistant->name ?? 'Ukjent',
                'from' => $shift->starts_at->format('d.m.Y'),
                'to' => $shift->ends_at->format('d.m.Y'),
            ])
            ->toArray();
    }

    #[Computed]
    public function employeesTotalPages(): int
    {
        return max(1, (int) ceil(Assistant::count() / $this->employeesPerPage));
    }

    #[Computed]
    public function employees(): array
    {
        $currentYear = Carbon::now()->year;

        $query = Assistant::query()
            ->withSum(['shifts as hours_this_year' => function ($query) use ($currentYear) {
                $query->worked()->forYear($currentYear);
            }], 'duration_minutes');

        if ($this->employeesSortDirection === 'desc') {
            $query->orderByDesc('hours_this_year');
        } else {
            $query->orderBy('hours_this_year');
        }

        return $query
            ->skip(($this->employeesPage - 1) * $this->employeesPerPage)
            ->take($this->employeesPerPage)
            ->get()
            ->map(fn (Assistant $assistant) => [
                'name' => $assistant->name,
                'position' => $assistant->type_label,
                'positionColor' => $this->getPositionColor($assistant->type),
                'email' => $this->truncateEmail($assistant->email),
                'phone' => $assistant->phone ?? '-',
                'hoursThisYear' => $this->formatMinutes((int) ($assistant->hours_this_year ?? 0)),
            ])
            ->toArray();
    }

    #[Computed]
    public function monthlyChartData(): array
    {
        $currentYear = Carbon::now()->year;
        $previousYear = $currentYear - 1;

        $months = collect(range(1, 12));

        $currentYearData = $this->getMonthlyHours($currentYear);
        $previousYearData = $this->getMonthlyHours($previousYear);

        $monthNames = [
            1 => 'januar', 2 => 'februar', 3 => 'mars', 4 => 'april',
            5 => 'mai', 6 => 'juni', 7 => 'juli', 8 => 'august',
            9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'desember',
        ];

        return $months->map(fn (int $month) => [
            'month' => $monthNames[$month],
            'current' => round(($currentYearData[$month] ?? 0) / 60, 1),
            'previous' => round(($previousYearData[$month] ?? 0) / 60, 1),
        ])->toArray();
    }

    #[Computed]
    public function percentageChartData(): array
    {
        $currentYear = Carbon::now()->year;
        $previousYear = $currentYear - 1;

        $hoursPerWeek = Setting::getBpaHoursPerWeek();
        $yearlyQuotaMinutes = $hoursPerWeek * 52 * 60;

        $currentYearData = $this->getMonthlyHours($currentYear);
        $previousYearData = $this->getMonthlyHours($previousYear);

        $monthNames = [
            1 => 'januar', 2 => 'februar', 3 => 'mars', 4 => 'april',
            5 => 'mai', 6 => 'juni', 7 => 'juli', 8 => 'august',
            9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'desember',
        ];

        // Calculate cumulative usage for each year
        $currentCumulative = 0;
        $previousCumulative = 0;

        return collect(range(1, 12))->map(function (int $month) use ($monthNames, $currentYearData, $previousYearData, $yearlyQuotaMinutes, $currentYear, &$currentCumulative, &$previousCumulative) {
            // Add this month's hours to cumulative total
            $currentCumulative += $currentYearData[$month] ?? 0;
            $previousCumulative += $previousYearData[$month] ?? 0;

            // Calculate percentage of yearly quota used (cumulative)
            $currentUsedPercent = $yearlyQuotaMinutes > 0 ? round(($currentCumulative / $yearlyQuotaMinutes) * 100) : 0;
            $previousUsedPercent = $yearlyQuotaMinutes > 0 ? round(($previousCumulative / $yearlyQuotaMinutes) * 100) : 0;

            // Remaining is 100% minus what's been used
            $remainingPercent = max(0, 100 - $currentUsedPercent);

            return [
                'month' => $monthNames[$month],
                'y'.($currentYear - 1) => $previousUsedPercent,
                'y'.$currentYear => $currentUsedPercent,
                'remaining' => $remainingPercent,
            ];
        })->toArray();
    }

    /**
     * Get monthly hours grouped by month for a given year.
     *
     * @return array<int, int>
     */
    private function getMonthlyHours(int $year): array
    {
        return Shift::query()
            ->worked()
            ->forYear($year)
            ->get()
            ->groupBy(fn (Shift $shift) => $shift->starts_at->month)
            ->map(fn (Collection $shifts) => $shifts->sum('duration_minutes'))
            ->toArray();
    }

    /**
     * Format minutes as HH:MM string.
     */
    private function formatMinutes(int|float|null $minutes): string
    {
        $minutes = (int) ($minutes ?? 0);
        $hours = intdiv(abs($minutes), 60);
        $mins = abs($minutes) % 60;
        $sign = $minutes < 0 ? '-' : '';

        return sprintf('%s%d:%02d', $sign, $hours, $mins);
    }

    /**
     * Get color for position type.
     */
    private function getPositionColor(string $type): string
    {
        return match ($type) {
            'primary' => 'accent',
            'substitute' => 'purple',
            'oncall' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Truncate email for display.
     */
    private function truncateEmail(?string $email): string
    {
        if (! $email) {
            return '-';
        }

        if (strlen($email) <= 12) {
            return $email;
        }

        return substr($email, 0, 10).'...';
    }

    public function render()
    {
        return view('livewire.bpa.dashboard');
    }
}
