<?php

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

    public function saveHomeAddress(): void
    {
        $this->validate([
            'homeAddress' => ['required', 'string', 'max:255'],
        ], [
            'homeAddress.required' => 'Hjemmeadressen er påkrevd.',
            'homeAddress.max' => 'Hjemmeadressen kan ikke være lengre enn 255 tegn.',
        ]);

        Setting::set('mileage_home_address', $this->homeAddress);

        $this->dispatch('notify', message: 'Hjemmeadresse lagret', type: 'success');
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
            $this->dispatch('notify', message: 'Du må lagre en hjemmeadresse først', type: 'error');

            return;
        }

        try {
            $client = new OpenRouteService;
            $distance = $client->calculateDistance($this->homeAddress, $this->newDestinationAddress);

            MileageDestination::create([
                'name' => $this->newDestinationName,
                'address' => $this->newDestinationAddress,
                'distance_km' => $distance,
            ]);

            $this->newDestinationName = '';
            $this->newDestinationAddress = '';

            $this->dispatch('notify', message: 'Destinasjon lagt til', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Kunne ikke beregne avstand: '.$e->getMessage(), type: 'error');
        }
    }

    public function deleteDestination(int $id): void
    {
        MileageDestination::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Destinasjon slettet', type: 'success');
    }

    public function recalculateDistance(int $id): void
    {
        if (empty($this->homeAddress)) {
            $this->dispatch('notify', message: 'Du må lagre en hjemmeadresse først', type: 'error');

            return;
        }

        try {
            $destination = MileageDestination::findOrFail($id);
            $client = new OpenRouteService;
            $distance = $client->calculateDistance($this->homeAddress, $destination->address);

            $destination->update(['distance_km' => $distance]);

            $this->dispatch('notify', message: 'Avstand oppdatert', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Kunne ikke beregne avstand: '.$e->getMessage(), type: 'error');
        }
    }

    public function getDisplayDistance(float $km): float
    {
        return $this->roundTrip ? $km * 2 : $km;
    }

    #[Computed]
    public function destinations()
    {
        return MileageDestination::orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.tools.mileage-calculator');
    }
}
