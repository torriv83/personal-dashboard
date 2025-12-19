<?php

namespace App\Livewire\Bpa;

use App\Livewire\Bpa\Calendar\Concerns\HandlesCalendarNavigation;
use App\Livewire\Bpa\Calendar\Concerns\HandlesCalendarViews;
use App\Livewire\Bpa\Calendar\Concerns\HandlesRecurringShifts;
use App\Livewire\Bpa\Calendar\Concerns\HandlesShiftCrud;
use App\Models\Assistant;
use App\Models\Setting;
use App\Models\Shift;
use App\Services\CalendarEvent;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read Collection $assistants
 * @property-read array $dayViewUnavailableAssistantIds
 * @property-read array $unavailableAssistantIds
 * @property-read Collection $shifts
 * @property-read array $shiftsByDate
 * @property-read Collection<int, CalendarEvent> $externalEvents
 * @property-read array $externalEventsByDate
 * @property-read array $remainingHoursData
 */
#[Layout('components.layouts.app')]
class Calendar extends Component
{
    use HandlesCalendarNavigation;
    use HandlesCalendarViews;
    use HandlesRecurringShifts;
    use HandlesShiftCrud;

    public int $year;

    public int $month;

    public int $day;

    public string $view = 'month'; // month, week, day

    // Modal state
    public bool $showModal = false;

    public ?int $editingShiftId = null;

    // Quick create state
    public bool $showQuickCreate = false;

    public string $quickCreateDate = '';

    public string $quickCreateTime = '';

    public ?string $quickCreateEndTime = null;

    public int $quickCreateX = 0;

    public int $quickCreateY = 0;

    // Form data
    public ?int $assistantId = null;

    public string $fromDate = '';

    public string $fromTime = '08:00';

    public string $toDate = '';

    public string $toTime = '16:00';

    public bool $isUnavailable = false;

    public bool $isAllDay = false;

    public string $note = '';

    // Recurring fields (only for unavailable entries)
    public bool $isRecurring = false;

    public string $recurringInterval = 'weekly'; // weekly, biweekly, monthly

    public string $recurringEndType = 'count'; // count, date

    public int $recurringCount = 4;

    public string $recurringEndDate = '';

    // Dialog for editing/deleting/moving recurring shifts
    public bool $showRecurringDialog = false;

    public string $recurringAction = ''; // edit, delete, archive, move

    public string $recurringScope = 'single'; // single, future, all

    // Track if shift being edited is already recurring
    public bool $isExistingRecurring = false;

    // Pending move data for recurring shifts
    public ?string $pendingMoveDate = null;

    public ?string $pendingMoveTime = null;

    protected $listeners = ['refreshCalendar' => '$refresh'];

    public array $norwegianMonths = [
        1 => 'Januar',
        2 => 'Februar',
        3 => 'Mars',
        4 => 'April',
        5 => 'Mai',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'August',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public array $norwegianDays = [
        'Man',
        'Tir',
        'Ons',
        'Tor',
        'Fre',
        'Lør',
        'Søn',
    ];

    public array $norwegianDaysFull = [
        'Mandag',
        'Tirsdag',
        'Onsdag',
        'Torsdag',
        'Fredag',
        'Lørdag',
        'Søndag',
    ];

    public function mount(): void
    {
        $now = Carbon::now('Europe/Oslo');
        $this->year = $now->year;
        $this->month = $now->month;
        $this->day = $now->day;

        // Open create modal if ?create=1 is in URL
        if (request()->query('create')) {
            $this->openModal($now->format('Y-m-d'));
            $this->dispatch('clear-url-params');
        }
    }

    /**
     * Handle bottom nav quick shift creation.
     */
    #[On('open-quick-shift-modal')]
    public function handleOpenQuickShiftModal(): void
    {
        $this->openModal(Carbon::now('Europe/Oslo')->format('Y-m-d'));
    }

    /**
     * Get all active assistants for the sidebar.
     */
    #[Computed]
    public function assistants(): Collection
    {
        return Assistant::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get assistant IDs that are unavailable for the entire current day (all-day unavailability).
     * Used for day view sidebar to disable dragging.
     *
     * @return array<int>
     */
    #[Computed]
    public function dayViewUnavailableAssistantIds(): array
    {
        $date = $this->getCurrentDateProperty();

        return Shift::query()
            ->where('is_unavailable', true)
            ->where('is_all_day', true)
            ->whereDate('starts_at', $date->format('Y-m-d'))
            ->pluck('assistant_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get assistant IDs that are unavailable for the currently selected time in the modal.
     *
     * @return array<int>
     */
    #[Computed]
    public function unavailableAssistantIds(): array
    {
        if (! $this->fromDate || ! $this->toDate) {
            return [];
        }

        // Don't filter if we're creating an unavailable entry
        if ($this->isUnavailable) {
            return [];
        }

        $startsAt = $this->isAllDay
            ? Carbon::parse($this->fromDate)->startOfDay()
            : Carbon::parse($this->fromDate.' '.($this->fromTime ?: '08:00'));

        $endsAt = $this->isAllDay
            ? Carbon::parse($this->toDate)->endOfDay()
            : Carbon::parse($this->toDate.' '.($this->toTime ?: '11:00'));

        return Shift::query()
            ->where('is_unavailable', true)
            ->when($this->editingShiftId, fn ($q) => $q->where('id', '!=', $this->editingShiftId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->pluck('assistant_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get shifts for the current visible date range.
     * Includes shifts that overlap with the visible range (for multi-day absences).
     */
    #[Computed]
    public function shifts(): Collection
    {
        $startDate = $this->getVisibleStartDate();
        $endDate = $this->getVisibleEndDate();

        return Shift::query()
            ->with('assistant')
            ->where('starts_at', '<=', $endDate)
            ->where('ends_at', '>=', $startDate)
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Get shifts grouped by date for easy template access.
     * Multi-day absences are expanded to appear on each day they span.
     */
    #[Computed]
    public function shiftsByDate(): array
    {
        $grouped = [];
        $visibleStart = $this->getVisibleStartDate()->startOfDay();
        $visibleEnd = $this->getVisibleEndDate()->endOfDay();

        foreach ($this->shifts() as $shift) {
            // For multi-day all-day absences, expand to each day
            if ($shift->is_unavailable && $shift->is_all_day) {
                $startDate = $shift->starts_at->copy()->startOfDay();
                $endDate = $shift->ends_at->copy()->startOfDay();

                // If it spans multiple days, add to each day
                if ($startDate->format('Y-m-d') !== $endDate->format('Y-m-d')) {
                    $currentDate = $startDate->copy();

                    while ($currentDate->lte($endDate)) {
                        // Only add to days within the visible range
                        if ($currentDate->gte($visibleStart) && $currentDate->lte($visibleEnd)) {
                            $date = $currentDate->format('Y-m-d');

                            if (! isset($grouped[$date])) {
                                $grouped[$date] = [];
                            }

                            $grouped[$date][] = $shift;
                        }

                        $currentDate->addDay();
                    }

                    continue;
                }
            }

            // Regular shifts or single-day absences: use starts_at date
            $date = $shift->starts_at->format('Y-m-d');

            if (! isset($grouped[$date])) {
                $grouped[$date] = [];
            }

            $grouped[$date][] = $shift;
        }

        return $grouped;
    }

    /**
     * Get shifts for a specific date.
     */
    public function getShiftsForDate(string $date): array
    {
        return $this->shiftsByDate[$date] ?? [];
    }

    /**
     * Get external calendar events for the current visible date range.
     *
     * @return Collection<int, CalendarEvent>
     */
    #[Computed]
    public function externalEvents(): Collection
    {
        $startDate = $this->getVisibleStartDate();
        $endDate = $this->getVisibleEndDate();

        return app(GoogleCalendarService::class)->getAllEvents($startDate, $endDate);
    }

    /**
     * Get external events grouped by date for easy template access.
     *
     * @return array<string, array<CalendarEvent>>
     */
    #[Computed]
    public function externalEventsByDate(): array
    {
        $grouped = [];

        foreach ($this->externalEvents as $event) {
            $date = $event->starts_at->format('Y-m-d');

            if (! isset($grouped[$date])) {
                $grouped[$date] = [];
            }

            $grouped[$date][] = $event;
        }

        return $grouped;
    }

    /**
     * Get external events for a specific date.
     *
     * @return array<CalendarEvent>
     */
    public function getExternalEventsForDate(string $date): array
    {
        return $this->externalEventsByDate[$date] ?? [];
    }

    /**
     * Get available years from actual shift data.
     *
     * @return array<int>
     */
    #[Computed]
    public function availableYears(): array
    {
        $years = Shift::query()
            ->withTrashed()
            ->pluck('starts_at')
            ->map(fn ($date) => Carbon::parse($date)->year)
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Always include current year
        $currentYear = Carbon::now('Europe/Oslo')->year;
        if (! in_array($currentYear, $years)) {
            $years[] = $currentYear;
            sort($years);
        }

        return $years;
    }

    /**
     * Get remaining hours data for the current year.
     * Includes both used hours and planned (upcoming) hours.
     *
     * @return array{remaining_minutes: int, remaining_formatted: string, quota_minutes: int}
     */
    #[Computed]
    public function remainingHoursData(): array
    {
        $currentYear = Carbon::now()->year;

        // Get BPA settings
        $hoursPerWeek = Setting::getBpaHoursPerWeek();
        $yearlyQuotaMinutes = $hoursPerWeek * 52 * 60;

        // Calculate all hours used/planned this year (both past and future shifts)
        $usedMinutes = Shift::query()
            ->worked()
            ->forYear($currentYear)
            ->sum('duration_minutes');

        $remainingMinutes = $yearlyQuotaMinutes - $usedMinutes;

        return [
            'remaining_minutes' => (int) $remainingMinutes,
            'remaining_formatted' => $this->formatMinutesForDisplay((int) $remainingMinutes),
            'quota_minutes' => (int) $yearlyQuotaMinutes,
        ];
    }

    /**
     * Format minutes as HH:MM string for display.
     */
    private function formatMinutesForDisplay(int $minutes): string
    {
        $hours = intdiv(abs($minutes), 60);
        $mins = abs($minutes) % 60;
        $sign = $minutes < 0 ? '-' : '';

        return sprintf('%s%d:%02d', $sign, $hours, $mins);
    }

    public function render()
    {
        return view('livewire.bpa.calendar');
    }
}
