<?php

namespace App\Livewire\Bpa;

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

    public function mount(): void
    {
        $now = Carbon::now('Europe/Oslo');
        $this->year = $now->year;
        $this->month = $now->month;
        $this->day = $now->day;
    }

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

        return $dayName.', '.$date->day.'. '.$this->norwegianMonths[$date->month].' '.$date->year;
    }

    public array $norwegianDaysFull = [
        'Mandag',
        'Tirsdag',
        'Onsdag',
        'Torsdag',
        'Fredag',
        'Lørdag',
        'Søndag',
    ];

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
        $totalMinutes = 15 * 60; // 15 hours (08:00 to 23:00)

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
            return $startOfWeek->day.' - '.$endOfWeek->day.'. '.$this->norwegianMonths[$startOfWeek->month].' '.$startOfWeek->year;
        }

        return $startOfWeek->day.'. '.$this->norwegianMonths[$startOfWeek->month].' - '.$endOfWeek->day.'. '.$this->norwegianMonths[$endOfWeek->month].' '.$endOfWeek->year;
    }

    public function getCurrentWeekNumberProperty(): int
    {
        return Carbon::create($this->year, $this->month, $this->day)->isoWeek();
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

    /**
     * Get shifts for a specific date.
     */
    public function getShiftsForDate(string $date): array
    {
        return $this->shiftsByDate[$date] ?? [];
    }

    /**
     * Open the modal for creating a new shift.
     */
    public function openModal(?string $date = null, ?string $time = null): void
    {
        $this->resetForm();
        $this->showModal = true;

        if ($date) {
            $this->fromDate = $date;
            $this->toDate = $date;
        } else {
            $today = Carbon::now('Europe/Oslo')->format('Y-m-d');
            $this->fromDate = $today;
            $this->toDate = $today;
        }

        if ($time) {
            $this->fromTime = $time;
            // Set end time 1 hour after start
            $parts = explode(':', $time);
            $endHour = min((int) $parts[0] + 1, 23);
            $this->toTime = sprintf('%02d:%s', $endHour, $parts[1] ?? '00');
        }
    }

    /**
     * Open the modal for editing an existing shift.
     */
    public function editShift(int $shiftId): void
    {
        $shift = Shift::findOrFail($shiftId);

        $this->editingShiftId = $shiftId;
        $this->assistantId = $shift->assistant_id;
        $this->fromDate = $shift->starts_at->format('Y-m-d');
        $this->fromTime = $shift->starts_at->format('H:i');
        $this->toDate = $shift->ends_at->format('Y-m-d');
        $this->toTime = $shift->ends_at->format('H:i');
        $this->isUnavailable = $shift->is_unavailable;
        $this->isAllDay = $shift->is_all_day;
        $this->note = $shift->note ?? '';
        $this->showModal = true;
    }

    /**
     * Close the modal and reset form.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Reset form to default values.
     */
    public function resetForm(): void
    {
        $this->editingShiftId = null;
        $this->assistantId = null;
        $this->fromDate = '';
        $this->fromTime = '08:00';
        $this->toDate = '';
        $this->toTime = '16:00';
        $this->isUnavailable = false;
        $this->isAllDay = false;
        $this->note = '';
    }

    /**
     * Save the shift (create or update).
     */
    public function saveShift(bool $createAnother = false): void
    {
        $this->validate([
            'assistantId' => 'required|exists:assistants,id',
            'fromDate' => 'required|date',
            'toDate' => 'required|date|after_or_equal:fromDate',
            'fromTime' => 'required_unless:isAllDay,true',
            'toTime' => 'required_unless:isAllDay,true',
        ], [
            'assistantId.required' => 'Velg en assistent',
            'assistantId.exists' => 'Ugyldig assistent',
            'fromDate.required' => 'Velg startdato',
            'toDate.required' => 'Velg sluttdato',
            'toDate.after_or_equal' => 'Sluttdato må være etter startdato',
        ]);

        $startsAt = $this->isAllDay
            ? Carbon::parse($this->fromDate)->startOfDay()
            : Carbon::parse($this->fromDate.' '.$this->fromTime);

        $endsAt = $this->isAllDay
            ? Carbon::parse($this->toDate)->endOfDay()
            : Carbon::parse($this->toDate.' '.$this->toTime);

        // Check for overlapping unavailability (only for work shifts, not unavailable entries)
        if (! $this->isUnavailable) {
            $conflict = Shift::findOverlappingUnavailability(
                $this->assistantId,
                $startsAt,
                $endsAt,
                $this->editingShiftId
            );

            if ($conflict) {
                $assistant = Assistant::find($this->assistantId);
                $conflictTime = $conflict->is_all_day
                    ? $conflict->starts_at->format('d.m.Y').' (hele dagen)'
                    : $conflict->starts_at->format('d.m.Y H:i').' - '.$conflict->ends_at->format('H:i');

                $this->dispatch('toast', type: 'error', message: "{$assistant->name} er borte: {$conflictTime}");

                return;
            }
        }

        $data = [
            'assistant_id' => $this->assistantId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_unavailable' => $this->isUnavailable,
            'is_all_day' => $this->isAllDay,
            'note' => $this->note ?: null,
        ];

        if ($this->editingShiftId) {
            $shift = Shift::findOrFail($this->editingShiftId);
            $shift->update($data);
            $this->dispatch('toast', type: 'success', message: 'Vakten ble oppdatert');
        } else {
            Shift::create($data);
            $this->dispatch('toast', type: 'success', message: 'Vakten ble opprettet');
        }

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);

        if ($createAnother) {
            $this->resetForm();
            $this->showModal = true;
            // Keep the same date for convenience
            $this->fromDate = $data['starts_at']->format('Y-m-d');
            $this->toDate = $data['starts_at']->format('Y-m-d');
        } else {
            $this->closeModal();
        }
    }

    /**
     * Delete a shift.
     */
    public function deleteShift(): void
    {
        if (! $this->editingShiftId) {
            return;
        }

        $shift = Shift::findOrFail($this->editingShiftId);
        $shift->delete();

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: 'Vakten ble slettet');
    }

    /**
     * Move a shift to a new date/time (for drag & drop).
     */
    public function moveShift(int $shiftId, string $newDate, ?string $newTime = null): void
    {
        $shift = Shift::findOrFail($shiftId);

        $oldStart = $shift->starts_at;
        $oldEnd = $shift->ends_at;
        $duration = $oldStart->diffInMinutes($oldEnd);

        if ($newTime) {
            $newStart = Carbon::parse($newDate.' '.$newTime);
        } else {
            // Keep same time, just change date
            $newStart = Carbon::parse($newDate.' '.$oldStart->format('H:i'));
        }

        $newEnd = $newStart->copy()->addMinutes($duration);

        // Check for overlapping unavailability (only for work shifts)
        if (! $shift->is_unavailable) {
            $conflict = Shift::findOverlappingUnavailability(
                $shift->assistant_id,
                $newStart,
                $newEnd,
                $shiftId
            );

            if ($conflict) {
                $conflictTime = $conflict->is_all_day
                    ? $conflict->starts_at->format('d.m.Y').' (hele dagen)'
                    : $conflict->starts_at->format('d.m.Y H:i').' - '.$conflict->ends_at->format('H:i');

                $this->dispatch('toast', type: 'error', message: "{$shift->assistant->name} er borte: {$conflictTime}");

                return;
            }
        }

        $shift->update([
            'starts_at' => $newStart,
            'ends_at' => $newEnd,
        ]);

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);
        $this->dispatch('toast', type: 'success', message: 'Vakten ble flyttet');
    }

    /**
     * Create a shift from sidebar drag & drop.
     */
    public function createShiftFromDrag(int $assistantId, string $date, ?string $time = null): void
    {
        $startsAt = $time
            ? Carbon::parse($date.' '.$time)
            : Carbon::parse($date)->setTime(8, 0);

        $endsAt = $startsAt->copy()->addHours(3); // Default 3 hour shift

        // Check for overlapping unavailability
        $conflict = Shift::findOverlappingUnavailability($assistantId, $startsAt, $endsAt);

        if ($conflict) {
            $assistant = Assistant::find($assistantId);
            $conflictTime = $conflict->is_all_day
                ? $conflict->starts_at->format('d.m.Y').' (hele dagen)'
                : $conflict->starts_at->format('d.m.Y H:i').' - '.$conflict->ends_at->format('H:i');

            $this->dispatch('toast', type: 'error', message: "{$assistant->name} er borte: {$conflictTime}");

            return;
        }

        Shift::create([
            'assistant_id' => $assistantId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_unavailable' => false,
            'is_all_day' => false,
        ]);

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);
        $this->dispatch('toast', type: 'success', message: 'Vakten ble opprettet');
    }

    /**
     * Resize a shift (change end time).
     */
    public function resizeShift(int $shiftId, int $newDurationMinutes): void
    {
        $shift = Shift::findOrFail($shiftId);
        if ($shift->is_all_day) {
            return;
        }

        // Minimum 15 minutes
        $newDurationMinutes = max(15, $newDurationMinutes);

        $newEndsAt = $shift->starts_at->copy()->addMinutes($newDurationMinutes);

        // Check for overlapping unavailability (only for work shifts)
        if (! $shift->is_unavailable) {
            $conflict = Shift::findOverlappingUnavailability(
                $shift->assistant_id,
                $shift->starts_at,
                $newEndsAt,
                $shiftId
            );

            if ($conflict) {
                $conflictTime = $conflict->is_all_day
                    ? $conflict->starts_at->format('d.m.Y').' (hele dagen)'
                    : $conflict->starts_at->format('d.m.Y H:i').' - '.$conflict->ends_at->format('H:i');

                $this->dispatch('toast', type: 'error', message: "{$shift->assistant->name} er borte: {$conflictTime}");

                return;
            }
        }

        $shift->update([
            'ends_at' => $newEndsAt,
        ]);

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);
        $this->dispatch('toast', type: 'success', message: 'Vakten ble endret');
    }

    /**
     * Open quick create popup.
     */
    public function openQuickCreate(string $date, string $time, ?string $endTime = null): void
    {
        $this->quickCreateDate = $date;
        $this->quickCreateTime = $time;
        $this->quickCreateEndTime = $endTime;
        $this->showQuickCreate = true;
    }

    /**
     * Close quick create popup.
     */
    public function closeQuickCreate(): void
    {
        $this->showQuickCreate = false;
        $this->quickCreateEndTime = null;
    }

    /**
     * Quick create shift from double-click or drag-to-create.
     */
    public function quickCreateShift(int $assistantId): void
    {
        $startsAt = Carbon::parse($this->quickCreateDate.' '.$this->quickCreateTime);

        // Use custom end time if provided (from drag-to-create), otherwise default to 3 hours
        if ($this->quickCreateEndTime) {
            $endsAt = Carbon::parse($this->quickCreateDate.' '.$this->quickCreateEndTime);
        } else {
            $endsAt = $startsAt->copy()->addHours(3);
        }

        Shift::create([
            'assistant_id' => $assistantId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_unavailable' => false,
            'is_all_day' => false,
        ]);

        $this->showQuickCreate = false;
        $this->quickCreateEndTime = null;

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);
        $this->dispatch('toast', type: 'success', message: 'Vakten ble opprettet');
    }

    /**
     * Duplicate a shift to another date.
     */
    public function duplicateShift(int $shiftId, string $targetDate): void
    {
        $shift = Shift::findOrFail($shiftId);

        $daysDiff = Carbon::parse($shift->starts_at->format('Y-m-d'))->diffInDays(Carbon::parse($targetDate), false);

        Shift::create([
            'assistant_id' => $shift->assistant_id,
            'starts_at' => $shift->starts_at->copy()->addDays($daysDiff),
            'ends_at' => $shift->ends_at->copy()->addDays($daysDiff),
            'is_unavailable' => $shift->is_unavailable,
            'is_all_day' => $shift->is_all_day,
            'note' => $shift->note,
        ]);

        // Clear computed property cache
        unset($this->shifts, $this->shiftsByDate);
        $this->dispatch('toast', type: 'success', message: 'Vakten ble duplisert');
    }

    public function render()
    {
        return view('livewire.bpa.calendar');
    }
}
