<?php

namespace App\Livewire\Medical;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\Prescription;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read array $stats
 * @property-read \Illuminate\Support\Collection $expiringPrescriptions
 * @property-read ?Prescription $nextExpiry
 */
#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    #[Computed]
    public function stats(): array
    {
        return [
            [
                'label' => 'Antall utstyr',
                'value' => (string) Equipment::count(),
                'icon' => 'box',
            ],
            [
                'label' => 'Kategorier',
                'value' => (string) Category::count(),
                'icon' => 'folder',
            ],
            [
                'label' => 'Resepter',
                'value' => (string) Prescription::count(),
                'icon' => 'document',
            ],
        ];
    }

    #[Computed]
    public function expiringPrescriptions(): \Illuminate\Support\Collection
    {
        return Prescription::where('valid_to', '<=', now()->addDays(30))
            ->orderBy('valid_to')
            ->get()
            ->map(function ($prescription) {
                $daysLeft = (int) Carbon::now()->startOfDay()->diffInDays($prescription->valid_to, false);

                $prescription->daysLeft = $daysLeft;
                $prescription->status = match (true) {
                    $daysLeft <= 0 => 'expired',
                    $daysLeft <= 7 => 'danger',
                    default => 'warning',
                };

                return $prescription;
            });
    }

    #[Computed]
    public function nextExpiry(): ?Prescription
    {
        return $this->expiringPrescriptions->first();
    }

    public function render()
    {
        return view('livewire.medical.dashboard');
    }
}
