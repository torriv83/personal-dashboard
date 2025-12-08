<?php

namespace App\Livewire\Medical;

use App\Models\WeightEntry;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read Collection<int, WeightEntry> $entries
 * @property-read array $stats
 * @property-read array $chartData
 */
#[Layout('components.layouts.app')]
class Weight extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    // Form fields
    public string $date = '';

    public string $time = '';

    public string $weight = '';

    public string $note = '';

    #[Computed]
    public function entries(): Collection
    {
        return WeightEntry::query()
            ->orderByDesc('recorded_at')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        $entries = $this->entries;

        if ($entries->isEmpty()) {
            return [
                'current' => null,
                'min' => null,
                'max' => null,
                'average' => null,
                'change' => null,
                'changePercent' => null,
            ];
        }

        $current = $entries->first()->weight;
        $oldest = $entries->last()->weight;
        $change = $entries->count() > 1 ? $current - $oldest : null;
        $changePercent = $change !== null && $oldest > 0
            ? round(($change / $oldest) * 100, 1)
            : null;

        return [
            'current' => $current,
            'min' => $entries->min('weight'),
            'max' => $entries->max('weight'),
            'average' => round($entries->avg('weight'), 1),
            'change' => $change,
            'changePercent' => $changePercent,
        ];
    }

    #[Computed]
    public function chartData(): array
    {
        return $this->entries
            ->sortBy('recorded_at')
            ->take(30)
            ->map(fn ($entry) => [
                'date' => $entry->recorded_at->format('d.m H:i'),
                'weight' => (float) $entry->weight,
            ])
            ->values()
            ->toArray();
    }

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
        $this->time = now()->format('H:i');
    }

    public function openModal(?int $id = null): void
    {
        $this->editingId = $id;

        if ($id) {
            /** @var WeightEntry|null $entry */
            $entry = WeightEntry::find($id);
            if ($entry) {
                $this->date = $entry->recorded_at->format('Y-m-d');
                $this->time = $entry->recorded_at->format('H:i');
                $this->weight = (string) $entry->weight;
                $this->note = $entry->note ?? '';
            }
        } else {
            $this->resetForm();
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'weight' => 'required|numeric|min:20|max:300',
            'note' => 'nullable|string|max:500',
        ]);

        $recordedAt = $validated['date'].' '.$validated['time'].':00';

        $data = [
            'recorded_at' => $recordedAt,
            'weight' => $validated['weight'],
            'note' => $validated['note'] ?: null,
        ];

        if ($this->editingId) {
            WeightEntry::find($this->editingId)?->update($data);
            $this->dispatch('toast', type: 'success', message: 'Vektregistrering oppdatert');
        } else {
            WeightEntry::create($data);
            $this->dispatch('toast', type: 'success', message: 'Vektregistrering lagret');
        }

        unset($this->entries, $this->stats, $this->chartData);
        $this->closeModal();
    }

    public function delete(int $id): void
    {
        WeightEntry::find($id)?->delete();
        unset($this->entries, $this->stats, $this->chartData);
        $this->dispatch('toast', type: 'success', message: 'Vektregistrering slettet');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->date = now()->format('Y-m-d');
        $this->time = now()->format('H:i');
        $this->weight = '';
        $this->note = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.medical.weight');
    }
}
