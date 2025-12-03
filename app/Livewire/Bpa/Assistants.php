<?php

namespace App\Livewire\Bpa;

use App\Models\Assistant;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read Collection $assistants
 * @property-read int $activeCount
 * @property-read int $totalCount
 */
class Assistants extends Component
{
    public bool $showAll = false;

    // Create form properties
    public string $createName = '';

    public ?int $createEmployeeNumber = null;

    public string $createEmail = '';

    public ?string $createPhone = null;

    public string $createType = 'primary';

    public string $createHiredAt = '';

    // Edit form properties
    public ?int $editingId = null;

    public string $editName = '';

    public string $editEmail = '';

    public ?string $editPhone = null;

    public string $editType = 'primary';

    public string $editHiredAt = '';

    public ?int $editEmployeeNumber = null;

    #[Computed]
    public function assistants(): Collection
    {
        if ($this->showAll) {
            return Assistant::query()
                ->withTrashed()
                ->orderByRaw('deleted_at IS NOT NULL')
                ->orderBy('employee_number')
                ->get();
        }

        return Assistant::query()->orderBy('employee_number')->get();
    }

    #[Computed]
    public function activeCount(): int
    {
        return Assistant::count();
    }

    #[Computed]
    public function totalCount(): int
    {
        return Assistant::withTrashed()->count();
    }

    public function editAssistant(int $id): void
    {
        $assistant = Assistant::withTrashed()->findOrFail($id);

        $this->editingId = $assistant->id;
        $this->editName = $assistant->name;
        $this->editEmail = $assistant->email;
        $this->editPhone = $assistant->phone;
        $this->editType = $assistant->type;
        $this->editHiredAt = $assistant->hired_at->format('Y-m-d');
        $this->editEmployeeNumber = $assistant->employee_number;

        $this->dispatch('open-modal', name: 'edit-assistant');
    }

    public function createAssistant(): void
    {
        $this->validate([
            'createName' => 'required|string|max:255',
            'createEmployeeNumber' => 'required|integer|unique:assistants,employee_number',
            'createEmail' => 'required|email|unique:assistants,email',
            'createPhone' => 'nullable|string|max:20',
            'createType' => 'required|in:primary,substitute,oncall',
            'createHiredAt' => 'required|date',
        ]);

        Assistant::create([
            'name' => $this->createName,
            'employee_number' => $this->createEmployeeNumber,
            'email' => $this->createEmail,
            'phone' => $this->createPhone,
            'type' => $this->createType,
            'hired_at' => $this->createHiredAt,
            'color' => match ($this->createType) {
                'primary' => '#3b82f6',
                'substitute' => '#a855f7',
                'oncall' => '#f97316',
                default => '#3b82f6',
            },
        ]);

        $this->resetCreateForm();
        $this->dispatch('close-modal', name: 'add-assistant');
        unset($this->assistants, $this->activeCount, $this->totalCount);
        $this->dispatch('toast', type: 'success', message: 'Assistenten ble opprettet');
    }

    public function updateAssistant(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editEmployeeNumber' => 'required|integer|unique:assistants,employee_number,'.$this->editingId,
            'editEmail' => 'required|email|unique:assistants,email,'.$this->editingId,
            'editPhone' => 'nullable|string|max:20',
            'editType' => 'required|in:primary,substitute,oncall',
            'editHiredAt' => 'required|date',
        ]);

        $assistant = Assistant::withTrashed()->findOrFail($this->editingId);
        $assistant->update([
            'name' => $this->editName,
            'employee_number' => $this->editEmployeeNumber,
            'email' => $this->editEmail,
            'phone' => $this->editPhone,
            'type' => $this->editType,
            'hired_at' => $this->editHiredAt,
        ]);

        $this->resetEditForm();
        $this->dispatch('close-modal', name: 'edit-assistant');
        unset($this->assistants);
        $this->dispatch('toast', type: 'success', message: 'Assistenten ble oppdatert');
    }

    public function deleteAssistant(int $id): void
    {
        $assistant = Assistant::findOrFail($id);
        $assistant->delete();

        unset($this->assistants, $this->activeCount, $this->totalCount);
        $this->dispatch('toast', type: 'success', message: 'Arbeidsforholdet ble avsluttet');
    }

    public function restoreAssistant(int $id): void
    {
        $assistant = Assistant::withTrashed()->findOrFail($id);
        $assistant->restore();

        unset($this->assistants, $this->activeCount, $this->totalCount);
        $this->dispatch('toast', type: 'success', message: 'Assistenten ble gjenopprettet');
    }

    public function forceDeleteAssistant(int $id): void
    {
        $assistant = Assistant::withTrashed()->findOrFail($id);
        $assistant->forceDelete();

        unset($this->assistants, $this->activeCount, $this->totalCount);
        $this->dispatch('toast', type: 'success', message: 'Assistenten ble permanent slettet');
    }

    private function resetCreateForm(): void
    {
        $this->createName = '';
        $this->createEmployeeNumber = null;
        $this->createEmail = '';
        $this->createPhone = null;
        $this->createType = 'primary';
        $this->createHiredAt = '';
    }

    private function resetEditForm(): void
    {
        $this->editingId = null;
        $this->editName = '';
        $this->editEmployeeNumber = null;
        $this->editEmail = '';
        $this->editPhone = null;
        $this->editType = 'primary';
        $this->editHiredAt = '';
    }

    public function render()
    {
        return view('livewire.bpa.assistants');
    }
}
