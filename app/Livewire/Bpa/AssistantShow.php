<?php

namespace App\Livewire\Bpa;

use App\Models\Assistant;
use App\Models\Shift;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read array $stats
 * @property-read LengthAwarePaginator $shifts
 * @property-read array $availableYears
 * @property-read Collection $upcomingUnavailability
 * @property-read string $employmentDuration
 */
class AssistantShow extends Component
{
    use WithPagination;

    public Assistant $assistant;

    #[Url]
    public ?int $year = null;

    #[Url]
    public ?int $month = null;

    public int $perPage = 25;

    // Edit form properties
    public string $editName = '';

    public ?int $editEmployeeNumber = null;

    public string $editEmail = '';

    public ?string $editPhone = null;

    public string $editType = 'primary';

    public string $editHiredAt = '';

    public function mount(Assistant $assistant): void
    {
        $this->assistant = $assistant;
        $this->year = $this->year ?? now()->year;
        $this->loadEditForm();
    }

    private function loadEditForm(): void
    {
        $this->editName = $this->assistant->name;
        $this->editEmployeeNumber = $this->assistant->employee_number;
        $this->editEmail = $this->assistant->email ?? '';
        $this->editPhone = $this->assistant->phone;
        $this->editType = $this->assistant->type;
        $this->editHiredAt = $this->assistant->hired_at->format('Y-m-d');
    }

    #[Computed]
    public function stats(): array
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;

        $hoursThisYear = $this->assistant->shifts()
            ->worked()
            ->forYear($currentYear)
            ->sum('duration_minutes');

        $hoursThisMonth = $this->assistant->shifts()
            ->worked()
            ->forMonth($currentYear, $currentMonth)
            ->sum('duration_minutes');

        $totalShifts = $this->assistant->shifts()
            ->worked()
            ->count();

        $averageMinutes = $totalShifts > 0
            ? intval($hoursThisYear / $totalShifts)
            : 0;

        return [
            'hours_this_year' => $this->formatMinutes($hoursThisYear),
            'hours_this_month' => $this->formatMinutes($hoursThisMonth),
            'total_shifts' => $totalShifts,
            'average_per_shift' => $this->formatMinutes($averageMinutes),
        ];
    }

    #[Computed]
    public function shifts(): LengthAwarePaginator
    {
        $query = $this->assistant->shifts()
            ->forYear($this->year);

        if ($this->month) {
            $query->whereMonth('starts_at', $this->month);
        }

        return $query->orderBy('starts_at', 'desc')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function availableYears(): array
    {
        $years = Shift::query()
            ->where('assistant_id', $this->assistant->id)
            ->selectRaw('YEAR(starts_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Ensure current year is always in the list
        if (! in_array(now()->year, $years)) {
            array_unshift($years, now()->year);
        }

        return $years;
    }

    #[Computed]
    public function upcomingUnavailability(): Collection
    {
        return $this->assistant->shifts()
            ->unavailable()
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addDays(30))
            ->orderBy('starts_at')
            ->get();
    }

    #[Computed]
    public function employmentDuration(): string
    {
        $diff = $this->assistant->hired_at->diff(now());

        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $diff->y.' '.($diff->y === 1 ? 'år' : 'år');
        }

        if ($diff->m > 0) {
            $parts[] = $diff->m.' '.($diff->m === 1 ? 'måned' : 'måneder');
        }

        if (empty($parts)) {
            return $diff->d.' '.($diff->d === 1 ? 'dag' : 'dager');
        }

        return implode(' og ', $parts);
    }

    public function updatedYear(): void
    {
        $this->month = null; // Reset month when year changes
        $this->resetPage();
    }

    public function updatedMonth(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updateAssistant(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editEmployeeNumber' => 'required|integer|unique:assistants,employee_number,'.$this->assistant->id,
            'editEmail' => 'required|email|unique:assistants,email,'.$this->assistant->id,
            'editPhone' => 'nullable|string|max:20',
            'editType' => 'required|in:primary,substitute,oncall',
            'editHiredAt' => 'required|date',
        ]);

        $this->assistant->update([
            'name' => $this->editName,
            'employee_number' => $this->editEmployeeNumber,
            'email' => $this->editEmail,
            'phone' => $this->editPhone,
            'type' => $this->editType,
            'hired_at' => $this->editHiredAt,
        ]);

        $this->assistant->refresh();
        unset($this->stats, $this->employmentDuration);

        $this->dispatch('close-modal', name: 'edit-assistant');
        $this->dispatch('toast', type: 'success', message: 'Assistenten ble oppdatert');
    }

    private function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%d:%02d', $hours, $mins);
    }

    public function render()
    {
        return view('livewire.bpa.assistant-show');
    }
}
