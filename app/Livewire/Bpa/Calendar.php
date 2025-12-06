<?php

namespace App\Livewire\Bpa;

use App\Livewire\Bpa\Calendar\Concerns\HandlesCalendarNavigation;
use App\Livewire\Bpa\Calendar\Concerns\HandlesCalendarViews;
use App\Livewire\Bpa\Calendar\Concerns\HandlesRecurringShifts;
use App\Livewire\Bpa\Calendar\Concerns\HandlesShiftCrud;
use App\Models\Assistant;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read Collection $assistants
 * @property-read array $dayViewUnavailableAssistantIds
 * @property-read array $unavailableAssistantIds
 * @property-read Collection $shifts
 * @property-read array $shiftsByDate
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

    // Dialog for editing/deleting recurring shifts
    public bool $showRecurringDialog = false;

    public string $recurringAction = ''; // edit, delete

    public string $recurringScope = 'single'; // single, future, all

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
     */
    #[Computed]
    public function shifts(): Collection
    {
        $startDate = $this->getVisibleStartDate();
        $endDate = $this->getVisibleEndDate();

        return Shift::query()
            ->with('assistant')
            ->where('starts_at', '>=', $startDate)
            ->where('starts_at', '<=', $endDate)
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Get shifts grouped by date for easy template access.
     */
    #[Computed]
    public function shiftsByDate(): array
    {
        $grouped = [];

        foreach ($this->shifts() as $shift) {
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

    public function render()
    {
        return view('livewire.bpa.calendar');
    }
}
