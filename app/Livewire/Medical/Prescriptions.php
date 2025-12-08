<?php

namespace App\Livewire\Medical;

use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Prescriptions extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    // Form fields
    public string $name = '';

    public string $validTo = '';

    #[Computed]
    public function prescriptions(): Collection
    {
        return Prescription::query()
            ->orderBy('valid_to')
            ->get()
            ->map(function (Prescription $prescription): array {
                $daysLeft = Carbon::now()->startOfDay()->diffInDays($prescription->valid_to, false);

                $status = 'ok';
                if ($daysLeft <= 0) {
                    $status = 'expired';
                } elseif ($daysLeft <= 7) {
                    $status = 'danger';
                } elseif ($daysLeft <= 30) {
                    $status = 'warning';
                }

                return [
                    'id' => $prescription->id,
                    'name' => $prescription->name,
                    'validTo' => $prescription->valid_to->format('Y-m-d'),
                    'daysLeft' => max(0, $daysLeft),
                    'status' => $status,
                ];
            });
    }

    public function mount(): void
    {
        // Open create modal if ?create=1 is in URL
        if (request()->query('create')) {
            $this->openModal();
            $this->dispatch('clear-url-params');
        }

        // Open edit modal if ?edit=ID is in URL
        if ($editId = request()->query('edit')) {
            $this->openModal((int) $editId);
            $this->dispatch('clear-url-params');
        }
    }

    public function openModal(?int $id = null): void
    {
        $this->editingId = $id;

        if ($id) {
            /** @var Prescription|null $prescription */
            $prescription = Prescription::find($id);
            if ($prescription) {
                $this->name = $prescription->name;
                $this->validTo = $prescription->valid_to->format('Y-m-d');
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
            'name' => 'required|string|max:255',
            'validTo' => 'required|date',
        ]);

        if ($this->editingId) {
            $prescription = Prescription::find($this->editingId);
            $prescription?->update([
                'name' => $validated['name'],
                'valid_to' => $validated['validTo'],
            ]);
            $this->dispatch('toast', type: 'success', message: 'Resepten ble oppdatert');
        } else {
            Prescription::create([
                'name' => $validated['name'],
                'valid_to' => $validated['validTo'],
            ]);
            $this->dispatch('toast', type: 'success', message: 'Resepten ble lagt til');
        }

        unset($this->prescriptions);
        $this->closeModal();
    }

    public function delete(int $id): void
    {
        Prescription::find($id)?->delete();
        unset($this->prescriptions);
        $this->dispatch('toast', type: 'success', message: 'Resepten ble slettet');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->validTo = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.medical.prescriptions');
    }
}
