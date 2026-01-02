<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Assistant;
use App\Models\Bookmark;
use App\Models\Equipment;
use App\Models\Prescription;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\WeightEntry;
use App\Models\WishlistItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Concurrency;
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
            ['name' => 'Gå til Bokmerker', 'url' => route('tools.bookmarks'), 'icon' => 'bookmark', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Oppgaver', 'url' => route('bpa.tasks.index'), 'icon' => 'check-square', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Økonomi', 'url' => route('economy'), 'icon' => 'dollar-sign', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Ønskeliste', 'url' => route('wishlist'), 'icon' => 'gift', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Innstillinger', 'url' => route('settings'), 'icon' => 'settings', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Profil', 'url' => route('profile'), 'icon' => 'user', 'category' => 'Navigasjon'],
            ['name' => 'Gå til Rømmers', 'url' => route('games.rommers'), 'icon' => 'dice', 'category' => 'Navigasjon'],

            // Verktøy
            ['name' => 'Portvelger', 'url' => route('tools.port-generator'), 'icon' => 'tool', 'category' => 'Verktøy'],
        ];
    }

    /**
     * Search results from all models.
     *
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    #[Computed]
    public function results(): Collection
    {
        if (strlen($this->search) < 2) {
            return collect($this->quickActions);
        }

        $searchTerm = '%' . strtolower($this->search) . '%';

        // Filter quick actions
        $filteredActions = collect($this->quickActions)
            ->filter(fn ($action) => str_contains(strtolower($action['name']), strtolower($this->search)));

        // Execute all database searches in parallel
        // Use static method calls to avoid serialization issues with $this in ProcessDriver
        try {
            [$assistants, $equipment, $prescriptions, $wishlistItems, $bookmarks, $taskLists] = Concurrency::run([
                fn () => self::searchAssistantsStatic($searchTerm),
                fn () => self::searchEquipmentStatic($searchTerm),
                fn () => self::searchPrescriptionsStatic($searchTerm),
                fn () => self::searchWishlistItemsStatic($searchTerm),
                fn () => self::searchBookmarksStatic($searchTerm),
                fn () => self::searchTaskListsStatic($searchTerm),
            ]);
        } catch (\Throwable $e) {
            // Fallback to sequential execution if parallel execution fails
            $assistants = $this->searchAssistants($searchTerm);
            $equipment = $this->searchEquipment($searchTerm);
            $prescriptions = $this->searchPrescriptions($searchTerm);
            $wishlistItems = $this->searchWishlistItems($searchTerm);
            $bookmarks = $this->searchBookmarks($searchTerm);
            $taskLists = $this->searchTaskLists($searchTerm);
        }

        // Merge all results
        return collect()
            ->merge($filteredActions)
            ->merge($assistants)
            ->merge($equipment)
            ->merge($prescriptions)
            ->merge($wishlistItems)
            ->merge($bookmarks)
            ->merge($taskLists)
            ->take(15);
    }

    /**
     * Search for assistants matching the given term.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private function searchAssistants(string $term): Collection
    {
        return Assistant::query()
            ->whereRaw('LOWER(name) LIKE ?', [$term])
            ->orWhereRaw('CAST(employee_number AS CHAR) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (Assistant $assistant) => [
                'name' => $assistant->name,
                'url' => route('bpa.assistants.show', $assistant),
                'icon' => 'user',
                'category' => 'Assistenter',
                'subtitle' => $assistant->formatted_number . ' · ' . $assistant->type_label,
            ]);
    }

    /**
     * Search for equipment matching the given term.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private function searchEquipment(string $term): Collection
    {
        return Equipment::query()
            ->whereRaw('LOWER(name) LIKE ?', [$term])
            ->orWhereRaw('LOWER(article_number) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (Equipment $item) => [
                'name' => $item->name,
                'url' => route('medical.equipment'),
                'icon' => 'package',
                'category' => 'Utstyr',
                'subtitle' => $item->article_number ?? 'Ingen artikkelnummer',
            ]);
    }

    /**
     * Search for prescriptions matching the given term.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private function searchPrescriptions(string $term): Collection
    {
        return Prescription::query()
            ->whereRaw('LOWER(name) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (Prescription $prescription) => [
                'name' => $prescription->name,
                'url' => route('medical.prescriptions'),
                'icon' => 'file-plus',
                'category' => 'Resepter',
                'subtitle' => 'Gyldig til ' . $prescription->valid_to->format('d.m.Y'),
            ]);
    }

    /**
     * Search for wishlist items matching the given term.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private function searchWishlistItems(string $term): Collection
    {
        return WishlistItem::query()
            ->whereRaw('LOWER(name) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (WishlistItem $item) => [
                'name' => $item->name,
                'url' => route('wishlist'),
                'icon' => 'gift',
                'category' => 'Ønskeliste',
                'subtitle' => number_format($item->price, 0, ',', ' ') . ' kr',
            ]);
    }

    /**
     * Search for bookmarks matching the given term.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private function searchBookmarks(string $term): Collection
    {
        return Bookmark::query()
            ->whereRaw('LOWER(title) LIKE ?', [$term])
            ->orWhereRaw('LOWER(url) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (Bookmark $bookmark) => [
                'name' => $bookmark->title,
                'url' => $bookmark->url,
                'icon' => 'bookmark',
                'category' => 'Bokmerker',
                'subtitle' => $bookmark->getDomain(),
            ]);
    }

    /**
     * Search for task lists matching the given term.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private function searchTaskLists(string $term): Collection
    {
        return TaskList::query()
            ->with('assistant')
            ->whereRaw('LOWER(name) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (TaskList $taskList) => [
                'name' => $taskList->name,
                'url' => route('bpa.tasks.show', $taskList),
                'icon' => 'check-square',
                'category' => 'Oppgaver',
                'subtitle' => $taskList->isAssignedToAssistant() ? $taskList->assistant->name : 'Ingen assistent',
            ]);
    }

    /**
     * Static version of searchAssistants for parallel execution.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private static function searchAssistantsStatic(string $term): Collection
    {
        return Assistant::query()
            ->whereRaw('LOWER(name) LIKE ?', [$term])
            ->orWhereRaw('CAST(employee_number AS CHAR) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (Assistant $assistant) => [
                'name' => $assistant->name,
                'url' => route('bpa.assistants.show', $assistant),
                'icon' => 'user',
                'category' => 'Assistenter',
                'subtitle' => $assistant->formatted_number . ' · ' . $assistant->type_label,
            ]);
    }

    /**
     * Static version of searchEquipment for parallel execution.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private static function searchEquipmentStatic(string $term): Collection
    {
        return Equipment::query()
            ->whereRaw('LOWER(name) LIKE ?', [$term])
            ->orWhereRaw('LOWER(article_number) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (Equipment $item) => [
                'name' => $item->name,
                'url' => route('medical.equipment'),
                'icon' => 'package',
                'category' => 'Utstyr',
                'subtitle' => $item->article_number ?? 'Ingen artikkelnummer',
            ]);
    }

    /**
     * Static version of searchPrescriptions for parallel execution.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private static function searchPrescriptionsStatic(string $term): Collection
    {
        return Prescription::query()
            ->whereRaw('LOWER(name) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (Prescription $prescription) => [
                'name' => $prescription->name,
                'url' => route('medical.prescriptions'),
                'icon' => 'file-plus',
                'category' => 'Resepter',
                'subtitle' => 'Gyldig til ' . $prescription->valid_to->format('d.m.Y'),
            ]);
    }

    /**
     * Static version of searchWishlistItems for parallel execution.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private static function searchWishlistItemsStatic(string $term): Collection
    {
        return WishlistItem::query()
            ->whereRaw('LOWER(name) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (WishlistItem $item) => [
                'name' => $item->name,
                'url' => route('wishlist'),
                'icon' => 'gift',
                'category' => 'Ønskeliste',
                'subtitle' => number_format($item->price, 0, ',', ' ') . ' kr',
            ]);
    }

    /**
     * Static version of searchBookmarks for parallel execution.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private static function searchBookmarksStatic(string $term): Collection
    {
        return Bookmark::query()
            ->whereRaw('LOWER(title) LIKE ?', [$term])
            ->orWhereRaw('LOWER(url) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (Bookmark $bookmark) => [
                'name' => $bookmark->title,
                'url' => $bookmark->url,
                'icon' => 'bookmark',
                'category' => 'Bokmerker',
                'subtitle' => $bookmark->getDomain(),
            ]);
    }

    /**
     * Static version of searchTaskLists for parallel execution.
     *
     * @param  string  $term  The search term (with wildcards already applied)
     * @return Collection<int, array{name: string, url: string, icon: string, category: string, subtitle: string}>
     */
    private static function searchTaskListsStatic(string $term): Collection
    {
        return TaskList::query()
            ->with('assistant')
            ->whereRaw('LOWER(name) LIKE ?', [$term])
            ->limit(5)
            ->get()
            ->map(fn (TaskList $taskList) => [
                'name' => $taskList->name,
                'url' => route('bpa.tasks.show', $taskList),
                'icon' => 'check-square',
                'category' => 'Oppgaver',
                'subtitle' => $taskList->isAssignedToAssistant() ? $taskList->assistant->name : 'Ingen assistent',
            ]);
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

        $this->dispatch('toast', type: 'success', message: 'Vekt registrert: ' . $this->weightInput . ' kg');
        $this->close();
    }

    public function render()
    {
        return view('livewire.command-palette');
    }
}
