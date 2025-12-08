<?php

namespace App\Livewire;

use App\Models\Assistant;
use App\Models\Equipment;
use App\Models\Prescription;
use App\Models\WeightEntry;
use App\Models\WishlistItem;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read array $quickActions
 * @property-read Collection $results
 */
class CommandPalette extends Component
{
    public string $search = '';

    public bool $isOpen = false;

    public ?string $actionMode = null;

    public string $weightInput = '';

    /**
     * Quick actions available in the command palette.
     *
     * @return array<int, array{name: string, url?: string, action?: string, icon: string, category: string}>
     */
    #[Computed]
    public function quickActions(): array
    {
        return [
            // Handlinger
            ['name' => 'Ny vakt', 'url' => route('bpa.calendar', ['create' => 1]), 'icon' => 'plus', 'category' => 'Handlinger'],
            ['name' => 'Legg til utstyr', 'url' => route('medical.equipment', ['create' => 1]), 'icon' => 'plus', 'category' => 'Handlinger'],
            ['name' => 'Legg til resept', 'url' => route('medical.prescriptions', ['create' => 1]), 'icon' => 'plus', 'category' => 'Handlinger'],
            ['name' => 'Registrer vekt', 'action' => 'weight', 'icon' => 'scale', 'category' => 'Handlinger'],
            ['name' => 'Legg til ønske', 'url' => route('wishlist', ['create' => 1]), 'icon' => 'plus', 'category' => 'Handlinger'],
            ['name' => 'Ny assistent', 'url' => route('bpa.assistants', ['create' => 1]), 'icon' => 'plus', 'category' => 'Handlinger'],

            // Navigasjon
            ['name' => 'Gå til Dashboard', 'url' => route('dashboard'), 'icon' => 'home', 'category' => 'Navigasjon'],
            ['name' => 'Gå til BPA', 'url' => route('bpa.dashboard'), 'icon' => 'clock', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Kalender', 'url' => route('bpa.calendar'), 'icon' => 'calendar', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Assistenter', 'url' => route('bpa.assistants'), 'icon' => 'users', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Timelister', 'url' => route('bpa.timesheets'), 'icon' => 'file-text', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Medisinsk', 'url' => route('medical.dashboard'), 'icon' => 'heart', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Utstyr', 'url' => route('medical.equipment'), 'icon' => 'package', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Resepter', 'url' => route('medical.prescriptions'), 'icon' => 'file-plus', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Vekt', 'url' => route('medical.weight'), 'icon' => 'activity', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Økonomi', 'url' => route('economy'), 'icon' => 'dollar-sign', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Ønskeliste', 'url' => route('wishlist'), 'icon' => 'gift', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Innstillinger', 'url' => route('settings'), 'icon' => 'settings', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Profil', 'url' => route('profile'), 'icon' => 'user', 'category' => 'Navigasjon'],

            // Verktøy
            ['name' => 'Portvelger', 'url' => route('tools.port-generator'), 'icon' => 'tool', 'category' => 'Verktøy'],
        ];
    }

    /**
     * Search results from all models.
     *
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle?: string}>
     */
    #[Computed]
    public function results(): Collection
    {
        if (strlen($this->search) < 2) {
            return collect($this->quickActions);
        }

        $searchTerm = '%'.strtolower($this->search).'%';
        $results = collect();

        // Filter quick actions
        $filteredActions = collect($this->quickActions)
            ->filter(fn ($action) => str_contains(strtolower($action['name']), strtolower($this->search)));
        $results = $results->merge($filteredActions);

        // Search Assistants
        $assistants = Assistant::query()
            ->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
            ->orWhereRaw('CAST(employee_number AS CHAR) LIKE ?', [$searchTerm])
            ->limit(5)
            ->get()
            ->map(fn (Assistant $assistant) => [
                'name' => $assistant->name,
                'url' => route('bpa.assistants.show', $assistant),
                'icon' => 'user',
                'category' => 'Assistenter',
                'subtitle' => $assistant->formatted_number.' · '.$assistant->type_label,
            ]);
        $results = $results->merge($assistants);

        // Search Equipment
        $equipment = Equipment::query()
            ->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
            ->orWhereRaw('LOWER(article_number) LIKE ?', [$searchTerm])
            ->limit(5)
            ->get()
            ->map(fn (Equipment $item) => [
                'name' => $item->name,
                'url' => route('medical.equipment'),
                'icon' => 'package',
                'category' => 'Utstyr',
                'subtitle' => $item->article_number ?? null,
            ]);
        $results = $results->merge($equipment);

        // Search Prescriptions
        $prescriptions = Prescription::query()
            ->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
            ->limit(5)
            ->get()
            ->map(fn (Prescription $prescription) => [
                'name' => $prescription->name,
                'url' => route('medical.prescriptions'),
                'icon' => 'file-plus',
                'category' => 'Resepter',
                'subtitle' => 'Gyldig til '.$prescription->valid_to->format('d.m.Y'),
            ]);
        $results = $results->merge($prescriptions);

        // Search Wishlist Items
        $wishlistItems = WishlistItem::query()
            ->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
            ->limit(5)
            ->get()
            ->map(fn (WishlistItem $item) => [
                'name' => $item->name,
                'url' => route('wishlist'),
                'icon' => 'gift',
                'category' => 'Ønskeliste',
                'subtitle' => number_format($item->price, 0, ',', ' ').' kr',
            ]);
        $results = $results->merge($wishlistItems);

        return $results->take(15);
    }

    public function open(): void
    {
        $this->isOpen = true;
        $this->search = '';
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->search = '';
        $this->actionMode = null;
        $this->weightInput = '';
    }

    public function startWeightRegistration(): void
    {
        $this->actionMode = 'weight';
        $this->weightInput = '';
    }

    public function cancelAction(): void
    {
        $this->actionMode = null;
        $this->weightInput = '';
    }

    public function saveWeight(): void
    {
        $this->validate([
            'weightInput' => 'required|numeric|min:20|max:300',
        ], [
            'weightInput.required' => 'Vekt er påkrevd',
            'weightInput.numeric' => 'Vekt må være et tall',
            'weightInput.min' => 'Vekt må være minst 20 kg',
            'weightInput.max' => 'Vekt kan ikke være over 300 kg',
        ]);

        WeightEntry::create([
            'weight' => $this->weightInput,
            'recorded_at' => now(),
        ]);

        $this->dispatch('toast', type: 'success', message: 'Vekt registrert: '.$this->weightInput.' kg');
        $this->close();
    }

    public function render()
    {
        return view('livewire.command-palette');
    }
}
