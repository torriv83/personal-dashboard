<?php

namespace App\Livewire\Bpa;

use App\Models\Assistant;
use App\Models\Shift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read Collection<int, Shift> $allShiftsForYear
 * @property-read Collection<int, Assistant> $assistants
 * @property-read array $availableYears
 * @property-read array $monthSummaries
 * @property-read LengthAwarePaginator $shifts
 * @property-read string $totalSum
 * @property-read string $averageTime
 * @property-read int $totalShiftCount
 * @property-read int $totalEntryCount
 */
#[Layout('components.layouts.app')]
class Timesheets extends Component
{
    use WithPagination;

    public ?int $selectedYear = null;

    public ?string $typeFilter = null;

    public int $perPage = 10;

    // Modal state
    public bool $showModal = false;

    public ?int $editingShiftId = null;

    // Form fields
    #[Validate('required|exists:assistants,id')]
    public ?int $assistant_id = null;

    #[Validate('required|date')]
    public string $date = '';

    #[Validate('required_without:is_all_day')]
    public string $start_time = '';

    #[Validate('required_without:is_all_day')]
    public string $end_time = '';

    public string $note = '';

    public bool $is_unavailable = false;

    public bool $is_all_day = false;

    public function mount(): void
    {
        $this->selectedYear = (int) date('Y');
    }

    public function setYear(?int $year): void
    {
        $this->selectedYear = $year;
        $this->resetPage();

        // Clear computed property caches
        unset($this->allShiftsForYear, $this->monthSummaries);
    }

    public function setTypeFilter(?string $type): void
    {
        $this->typeFilter = $type;
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function toggleField(int $shiftId, string $field): void
    {
        $shift = Shift::find($shiftId);
        if (! $shift) {
            return;
        }

        $dbField = match ($field) {
            'away' => 'is_unavailable',
            'fullDay' => 'is_all_day',
            'archived' => 'is_archived',
            default => null,
        };

        if ($dbField) {
            $shift->{$dbField} = ! $shift->{$dbField};
            $shift->save();

            $fieldName = match ($field) {
                'away' => 'Borte',
                'fullDay' => 'Hel dag',
                'archived' => 'Arkivert',
                default => 'Status',
            };

            $status = $shift->{$dbField} ? 'aktivert' : 'deaktivert';
            $this->dispatch('toast', type: 'success', message: "{$fieldName} {$status}");
        }
    }

    // Modal methods
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function openEditModal(int $shiftId): void
    {
        $shift = Shift::find($shiftId);
        if (! $shift) {
            return;
        }

        $this->editingShiftId = $shiftId;
        $this->assistant_id = $shift->assistant_id;
        $this->date = $shift->starts_at->format('Y-m-d');
        $this->start_time = $shift->starts_at->format('H:i');
        $this->end_time = $shift->ends_at->format('H:i');
        $this->note = $shift->note ?? '';
        $this->is_unavailable = $shift->is_unavailable;
        $this->is_all_day = $shift->is_all_day;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate();

        $startsAt = $this->date.' '.($this->is_all_day ? '00:00' : $this->start_time);
        $endsAt = $this->date.' '.($this->is_all_day ? '23:59' : $this->end_time);

        $data = [
            'assistant_id' => $this->assistant_id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'note' => $this->note ?: null,
            'is_unavailable' => $this->is_unavailable,
            'is_all_day' => $this->is_all_day,
            'is_archived' => false,
        ];

        if ($this->editingShiftId) {
            $shift = Shift::find($this->editingShiftId);
            $shift?->update($data);
            $this->dispatch('toast', type: 'success', message: 'Oppføringen ble oppdatert');
        } else {
            Shift::create($data);
            $this->dispatch('toast', type: 'success', message: 'Oppføringen ble opprettet');
        }

        $this->closeModal();
    }

    public function delete(int $shiftId): void
    {
        Shift::find($shiftId)?->delete();
        $this->dispatch('toast', type: 'success', message: 'Oppføringen ble slettet');
    }

    private function resetForm(): void
    {
        $this->editingShiftId = null;
        $this->assistant_id = null;
        $this->date = '';
        $this->start_time = '';
        $this->end_time = '';
        $this->note = '';
        $this->is_unavailable = false;
        $this->is_all_day = false;
        $this->resetValidation();
    }

    #[Computed]
    public function shifts(): LengthAwarePaginator
    {
        $query = Shift::with('assistant')
            ->orderByDesc('starts_at');

        if ($this->selectedYear !== null) {
            $query->forYear($this->selectedYear);
        }

        // Apply type filter
        match ($this->typeFilter) {
            'worked' => $query->where('is_unavailable', false)->where('is_archived', false),
            'away' => $query->where('is_unavailable', true),
            'fullday' => $query->where('is_all_day', true),
            'archived' => $query->where('is_archived', true),
            default => null,
        };

        return $query->paginate($this->perPage);
    }

    /**
     * Get all worked shifts for the selected year (for statistics).
     * Excludes unavailable/away shifts from hour calculations.
     *
     * @return Collection<int, Shift>
     */
    #[Computed]
    public function allShiftsForYear(): Collection
    {
        $query = Shift::query()
            ->where('is_unavailable', false);

        if ($this->selectedYear !== null) {
            $query->forYear($this->selectedYear);
        }

        return $query->get(['duration_minutes', 'starts_at']);
    }

    #[Computed]
    public function assistants(): Collection
    {
        return Assistant::orderBy('name')->get();
    }

    #[Computed]
    public function availableYears(): array
    {
        $years = Shift::query()
            ->pluck('starts_at')
            ->map(fn ($date) => $date->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        return $years ?: [(int) date('Y')];
    }

    #[Computed]
    public function monthSummaries(): array
    {
        $monthNames = [
            12 => 'Des', 11 => 'Nov', 10 => 'Okt', 9 => 'Sep',
            8 => 'Aug', 7 => 'Jul', 6 => 'Jun', 5 => 'Mai',
            4 => 'Apr', 3 => 'Mar', 2 => 'Feb', 1 => 'Jan',
        ];

        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        // Calculate minutes per month
        $minutesByMonth = [];
        foreach ($this->allShiftsForYear as $shift) {
            $month = $shift->starts_at->month;
            $minutesByMonth[$month] = ($minutesByMonth[$month] ?? 0) + ($shift->duration_minutes ?? 0);
        }

        $summaries = [];
        foreach ($monthNames as $monthNum => $name) {
            $minutes = $minutesByMonth[$monthNum] ?? 0;

            // Determine if this month should be shown
            $showMonth = false;
            if ($this->selectedYear !== null && $this->selectedYear < $currentYear) {
                // Past year: show all 12 months (even with 0 hours)
                $showMonth = true;
            } elseif ($this->selectedYear === $currentYear) {
                // Current year: show months up to current month, or future months with data
                $showMonth = ($monthNum <= $currentMonth) || ($minutes > 0);
            } elseif ($this->selectedYear !== null && $this->selectedYear > $currentYear) {
                // Future year: only show months with data
                $showMonth = ($minutes > 0);
            }

            if ($showMonth) {
                $summaries[$monthNum] = [
                    'month' => $name,
                    'minutes' => $minutes,
                    'formatted' => number_format($minutes / 60, 2, '.', ''),
                ];
            }
        }

        return $summaries;
    }

    #[Computed]
    public function totalSum(): string
    {
        $totalMinutes = $this->allShiftsForYear->sum('duration_minutes');

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    #[Computed]
    public function averageTime(): string
    {
        $count = $this->allShiftsForYear->count();
        if ($count === 0) {
            return '00:00';
        }

        $avgMinutes = $this->allShiftsForYear->sum('duration_minutes') / $count;
        $hours = intdiv((int) $avgMinutes, 60);
        $minutes = (int) round($avgMinutes % 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Total count of actual shifts (excluding unavailable/away entries).
     */
    #[Computed]
    public function totalShiftCount(): int
    {
        return $this->allShiftsForYear->count();
    }

    /**
     * Total count of all entries including unavailable (for pagination display).
     */
    #[Computed]
    public function totalEntryCount(): int
    {
        $query = Shift::query();

        if ($this->selectedYear !== null) {
            $query->forYear($this->selectedYear);
        }

        return $query->count();
    }

    public function render()
    {
        return view('livewire.bpa.timesheets');
    }
}
