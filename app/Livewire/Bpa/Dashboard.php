<?php

namespace App\Livewire\Bpa;

use App\Models\Assistant;
use App\Models\Setting;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * @property-read array $stats
 * @property-read int $assistantCount
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
 * @property-read EloquentCollection<int, Assistant> $allAssistants
 */
class Dashboard extends Component
{
    /**
     * Pagination section configuration.
     * Maps section name to [pageProperty, computedProperty].
     */
    private const PAGINATION_SECTIONS = [
        'weekly' => ['weeklyPage', 'weeklyHours'],
        'shifts' => ['shiftsPage', 'upcomingShifts'],
        'employees' => ['employeesPage', 'employees'],
    ];

    /**
     * Default widget order configuration.
     */
    private const DEFAULT_STAT_ORDER = ['stat_assistants', 'stat_hours_used', 'stat_hours_remaining', 'stat_hours_week'];

    private const DEFAULT_WIDGET_ORDER = ['chart_monthly', 'chart_percentage', 'table_weekly', 'table_shifts', 'table_unavailable', 'table_employees'];

    // Edit mode for drag & drop
    public bool $editMode = false;

    /** @var array<int, string> */
    public array $statCardOrder = [];

    /** @var array<int, string> */
    public array $widgetOrder = [];

    // Pagination state
    public int $weeklyPerPage = 10;

    public int $weeklyPage = 1;

    public int $shiftsPerPage = 10;

    public int $shiftsPage = 1;

    public int $employeesPerPage = 3;

    public int $employeesPage = 1;

    public string $employeesSortDirection = 'desc';

    // Quick add unavailable form state
    public bool $showQuickAddForm = false;

    #[Validate('required|exists:assistants,id')]
    public ?int $quickAddAssistantId = null;

    #[Validate('required|date')]
    public string $quickAddFromDate = '';

    #[Validate('required|date|after_or_equal:quickAddFromDate')]
    public string $quickAddToDate = '';

    public function mount(): void
    {
        // Load saved order from settings, or use defaults
        $savedStatOrder = Setting::get('bpa_dashboard_stat_order');
        $this->statCardOrder = $savedStatOrder ? json_decode($savedStatOrder, true) : self::DEFAULT_STAT_ORDER;

        $savedWidgetOrder = Setting::get('bpa_dashboard_widget_order');
        $this->widgetOrder = $savedWidgetOrder ? json_decode($savedWidgetOrder, true) : self::DEFAULT_WIDGET_ORDER;
    }

    public function toggleEditMode(): void
    {
        $this->editMode = ! $this->editMode;
    }

    public function updateStatCardOrder(string $item, int $position): void
    {
        // Remove the item from its current position
        $order = array_values(array_diff($this->statCardOrder, [$item]));

        // Insert at the new position
        array_splice($order, $position, 0, $item);

        $this->statCardOrder = $order;
        Setting::set('bpa_dashboard_stat_order', json_encode($order));
    }

    public function updateWidgetOrder(string $item, int $position): void
    {
        // Remove the item from its current position
        $order = array_values(array_diff($this->widgetOrder, [$item]));

        // Insert at the new position
        array_splice($order, $position, 0, $item);

        $this->widgetOrder = $order;
        Setting::set('bpa_dashboard_widget_order', json_encode($order));
    }

    // Generic pagination methods
    public function nextPage(string $section): void
    {
        [$pageProperty, $computedProperty] = self::PAGINATION_SECTIONS[$section];
        $this->{$pageProperty}++;
        unset($this->{$computedProperty});
    }

    public function prevPage(string $section): void
    {
        [$pageProperty, $computedProperty] = self::PAGINATION_SECTIONS[$section];
        if ($this->{$pageProperty} > 1) {
            $this->{$pageProperty}--;
            unset($this->{$computedProperty});
        }
    }

    private function resetPagination(string $section): void
    {
        [$pageProperty, $computedProperty] = self::PAGINATION_SECTIONS[$section];
        $this->{$pageProperty} = 1;
        unset($this->{$computedProperty});
    }

    // Lifecycle hooks for per-page changes
    public function updatedWeeklyPerPage(): void
    {
        $this->resetPagination('weekly');
    }

    public function updatedShiftsPerPage(): void
    {
        $this->resetPagination('shifts');
    }

    public function updatedEmployeesPerPage(): void
    {
        $this->resetPagination('employees');
    }

    public function sortEmployeesByHours(): void
    {
        $this->employeesSortDirection = $this->employeesSortDirection === 'desc' ? 'asc' : 'desc';
        $this->resetPagination('employees');
    }

    // Quick add unavailable methods
    public function openQuickAddForm(): void
    {
        $this->showQuickAddForm = true;
        $this->quickAddFromDate = now()->format('Y-m-d');
        $this->quickAddToDate = now()->format('Y-m-d');
    }

    public function closeQuickAddForm(): void
    {
        $this->showQuickAddForm = false;
        $this->resetQuickAddForm();
    }

    public function saveQuickAdd(): void
    {
        $this->validate([
            'quickAddAssistantId' => 'required|exists:assistants,id',
            'quickAddFromDate' => 'required|date',
            'quickAddToDate' => 'required|date|after_or_equal:quickAddFromDate',
        ]);

        Shift::create([
            'assistant_id' => $this->quickAddAssistantId,
            'starts_at' => $this->quickAddFromDate.' 00:00:00',
            'ends_at' => $this->quickAddToDate.' 23:59:59',
            'is_unavailable' => true,
            'is_all_day' => true,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Fravær lagt til');
        $this->closeQuickAddForm();
        unset($this->unavailableEmployees);
    }

    private function resetQuickAddForm(): void
    {
        $this->quickAddAssistantId = null;
        $this->quickAddFromDate = '';
        $this->quickAddToDate = '';
        $this->resetValidation();
    }

    #[Computed]
    public function allAssistants(): EloquentCollection
    {
        return Assistant::orderBy('name')->get();
    }

    /**
     * Shared assistant count to avoid redundant COUNT queries.
     */
    #[Computed]
    public function assistantCount(): int
    {
        return Assistant::count();
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
            'stat_assistants' => [
                'id' => 'stat_assistants',
                'label' => 'Antall Assistenter',
                'value' => (string) $this->assistantCount,
                'description' => null,
                'link' => route('bpa.assistants'),
            ],
            'stat_hours_used' => [
                'id' => 'stat_hours_used',
                'label' => 'Timer brukt i år',
                'value' => $this->formatMinutes($usedThisYearMinutes),
                'valueSuffix' => '('.$this->formatMinutes($usedThisYearMinutes + $plannedMinutes).' med planlagt)',
                'description' => $this->formatMinutes($usedThisMonthMinutes).' brukt av '.$this->formatMinutes((int) $monthlyQuotaMinutes).' denne måneden | '.$this->formatMinutes($plannedMinutes).' planlagt ut året',
            ],
            'stat_hours_remaining' => [
                'id' => 'stat_hours_remaining',
                'label' => 'Timer igjen',
                'value' => $this->formatMinutes($remainingMinutes),
                'valueSuffix' => '('.$this->formatMinutes($remainingWithPlannedMinutes).' med planlagt)',
                'description' => $this->formatMinutes($averageRemainingPerWeekMinutes).' snitt/uke ut året | '.$this->formatMinutes($averageRemainingWithPlannedPerWeekMinutes).' snitt/uke med planlagt',
            ],
            'stat_hours_week' => [
                'id' => 'stat_hours_week',
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
        $driver = DB::getDriverName();

        // Use SQL COUNT(DISTINCT) for week counting - much faster than PHP groupBy
        $weekExpression = $driver === 'sqlite'
            ? "strftime('%W', starts_at)"
            : 'WEEK(starts_at, 3)';

        $weekCount = Shift::query()
            ->worked()
            ->where('starts_at', '>=', $now->copy()->subWeeks(52)->startOfWeek())
            ->where('starts_at', '<=', $now)
            ->selectRaw("COUNT(DISTINCT {$weekExpression}) as week_count")
            ->value('week_count');

        return max(1, (int) ceil($weekCount / $this->weeklyPerPage));
    }

    #[Computed]
    public function weeklyHours(): array
    {
        $now = Carbon::now();
        $driver = DB::getDriverName();

        // Use database-agnostic week expression
        $weekExpression = $driver === 'sqlite'
            ? "strftime('%W', starts_at)"
            : 'WEEK(starts_at, 3)';

        // Use SQL GROUP BY instead of PHP groupBy - 10-50x faster
        // Use toBase() to get stdClass objects instead of Shift models
        $results = Shift::query()
            ->selectRaw("{$weekExpression} as week_number")
            ->selectRaw('SUM(duration_minutes) as total_minutes')
            ->selectRaw('COUNT(*) as shift_count')
            ->worked()
            ->where('starts_at', '>=', $now->copy()->subWeeks(52)->startOfWeek())
            ->where('starts_at', '<=', $now)
            ->groupByRaw($weekExpression)
            ->orderByDesc('week_number')
            ->toBase()
            ->get();

        return $results
            ->skip(($this->weeklyPage - 1) * $this->weeklyPerPage)
            ->take($this->weeklyPerPage)
            ->map(function (object $row) {
                $avgMinutes = $row->shift_count > 0
                    ? (int) ($row->total_minutes / $row->shift_count)
                    : 0;

                return [
                    'week' => (int) $row->week_number,
                    'total' => $this->formatMinutes((int) $row->total_minutes),
                    'average' => $this->formatMinutes($avgMinutes),
                    'count' => (int) $row->shift_count,
                ];
            })
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
                'id' => $shift->id,
                'name' => $shift->assistant->name ?? 'Ukjent',
                'from' => $shift->starts_at->format('d.m.Y'),
                'to' => $shift->ends_at->format('d.m.Y'),
            ])
            ->toArray();
    }

    public function deleteUnavailable(int $shiftId): void
    {
        Shift::find($shiftId)?->delete();
        $this->dispatch('toast', type: 'success', message: 'Fravær slettet');
        unset($this->unavailableEmployees);
    }

    #[Computed]
    public function employeesTotalPages(): int
    {
        return max(1, (int) ceil($this->assistantCount / $this->employeesPerPage));
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
     * Uses SQL GROUP BY instead of PHP groupBy for better performance.
     *
     * @return array<int, int>
     */
    private function getMonthlyHours(int $year): array
    {
        $driver = DB::getDriverName();

        // Use database-agnostic month expression
        $monthExpression = $driver === 'sqlite'
            ? "CAST(strftime('%m', starts_at) AS INTEGER)"
            : 'MONTH(starts_at)';

        return Shift::query()
            ->selectRaw("{$monthExpression} as month")
            ->selectRaw('SUM(duration_minutes) as total_minutes')
            ->worked()
            ->forYear($year)
            ->groupByRaw($monthExpression)
            ->get()
            ->pluck('total_minutes', 'month')
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
