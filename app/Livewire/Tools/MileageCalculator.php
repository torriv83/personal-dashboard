<?php

declare(strict_types=1);

namespace App\Livewire\Tools;

use App\Models\MileageDestination;
use App\Models\Setting;
use App\Services\OpenRouteService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MileageCalculator extends Component
{
    public string $homeAddress = '';

    public bool $roundTrip = true;

    public ?int $editingDestinationId = null;

    public string $newDestinationName = '';

    public string $newDestinationAddress = '';

    public function mount(): void
    {
        $this->homeAddress = Setting::get('mileage_home_address', '');
    }

    public function addDestination(): void
    {
        $this->validate([
            'newDestinationName' => ['required', 'string', 'max:255'],
            'newDestinationAddress' => ['required', 'string', 'max:255'],
        ], [
            'newDestinationName.required' => 'Navn er påkrevd.',
            'newDestinationName.max' => 'Navn kan ikke være lengre enn 255 tegn.',
            'newDestinationAddress.required' => 'Adresse er påkrevd.',
            'newDestinationAddress.max' => 'Adresse kan ikke være lengre enn 255 tegn.',
        ]);

        if (empty($this->homeAddress)) {
            $this->dispatch('toast', message: 'Du må lagre en hjemmeadresse først', type: 'error');

            return;
        }

        try {
            $client = new OpenRouteService;
            $distance = $client->calculateDistance($this->homeAddress, $this->newDestinationAddress);

            if ($distance === null) {
                $this->dispatch('toast', message: 'Kunne ikke finne adressen. Prøv en mer spesifikk adresse.', type: 'error');

                return;
            }

            $maxSortOrder = MileageDestination::max('sort_order') ?? -1;

            MileageDestination::create([
                'name' => $this->newDestinationName,
                'address' => $this->newDestinationAddress,
                'distance_km' => $distance,
                'sort_order' => $maxSortOrder + 1,
            ]);

            $this->newDestinationName = '';
            $this->newDestinationAddress = '';

            $this->dispatch('toast', message: 'Destinasjon lagt til', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Kunne ikke beregne avstand: ' . $e->getMessage(), type: 'error');
        }
    }

    public function deleteDestination(int $id): void
    {
        MileageDestination::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Destinasjon slettet', type: 'success');
    }

    public function recalculateDistance(int $id): void
    {
        if (empty($this->homeAddress)) {
            $this->dispatch('toast', message: 'Du må lagre en hjemmeadresse først', type: 'error');

            return;
        }

        try {
            $destination = MileageDestination::findOrFail($id);
            $client = new OpenRouteService;
            $distance = $client->calculateDistance($this->homeAddress, $destination->address);

            if ($distance === null) {
                $this->dispatch('toast', message: 'Kunne ikke finne adressen. Prøv å oppdatere til en mer spesifikk adresse.', type: 'error');

                return;
            }

            $destination->update(['distance_km' => $distance]);

            $this->dispatch('toast', message: 'Avstand oppdatert', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Kunne ikke beregne avstand: ' . $e->getMessage(), type: 'error');
        }
    }

    public function getDisplayDistance(float $km): float
    {
        return $this->roundTrip ? $km * 2 : $km;
    }

    public function updateOrder(string $item, int $position): void
    {
        $destinationId = (int) $item;
        $destinations = MileageDestination::orderBy('sort_order')->pluck('id')->toArray();

        // Remove the item from its current position
        $destinations = array_values(array_diff($destinations, [$destinationId]));

        // Insert at the new position
        array_splice($destinations, $position, 0, $destinationId);

        // Update all sort orders
        foreach ($destinations as $index => $id) {
            MileageDestination::where('id', $id)->update(['sort_order' => $index]);
        }
    }

    #[Computed]
    public function destinations()
    {
        return MileageDestination::orderBy('sort_order')->get();
    }

    public function render()
    {
        return view('livewire.tools.mileage-calculator');
    }
}
