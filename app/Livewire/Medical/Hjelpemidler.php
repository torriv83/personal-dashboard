<?php

declare(strict_types=1);

namespace App\Livewire\Medical;

use App\Models\Hjelpemiddel;
use App\Models\HjelpemiddelKategori;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read Collection<int, HjelpemiddelKategori> $kategorier
 * @property-read string $brukerpass
 */
#[Layout('components.layouts.app')]
class Hjelpemidler extends Component
{
    // Modal states
    public bool $showItemModal = false;

    public bool $showKategoriModal = false;

    public bool $showBrukerpassModal = false;

    // Form data for hjelpemiddel
    public ?int $editingItemId = null;

    public ?int $editingItemKategoriId = null;

    public ?int $editingItemParentId = null;

    public string $itemName = '';

    public string $itemUrl = '';

    /** @var array<int, array{key: string, value: string}> */
    public array $itemCustomFields = [];

    // Form data for kategori
    public ?int $editingKategoriId = null;

    public string $kategoriName = '';

    // Brukerpass
    public string $brukerpassValue = '';

    #[Computed]
    public function brukerpass(): string
    {
        return Setting::get('hjelpemidler_brukerpass', '');
    }

    /**
     * @return Collection<int, HjelpemiddelKategori>
     */
    #[Computed]
    public function kategorier(): Collection
    {
        return HjelpemiddelKategori::query()
            ->with(['hjelpemidler' => fn ($query) => $query
                ->whereNull('parent_id')
                ->with('children.children') // Support 2 levels of nesting
                ->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();
    }

    public function openItemModal(?int $id = null, ?int $kategoriId = null, ?int $parentId = null): void
    {
        $this->resetItemForm();
        $this->editingItemId = $id;
        $this->editingItemKategoriId = $kategoriId;
        $this->editingItemParentId = $parentId;

        if ($id) {
            $item = Hjelpemiddel::find($id);
            if ($item) {
                $this->itemName = $item->name;
                $this->itemUrl = $item->url ?? '';
                $this->itemCustomFields = $item->custom_fields ?? [];
                $this->editingItemKategoriId = $item->hjelpemiddel_kategori_id;
                $this->editingItemParentId = $item->parent_id;
            }
        }

        // If adding child to a parent, inherit the category
        if ($parentId && ! $kategoriId) {
            $parent = Hjelpemiddel::find($parentId);
            if ($parent) {
                $this->editingItemKategoriId = $parent->hjelpemiddel_kategori_id;
            }
        }

        $this->showItemModal = true;
    }

    public function closeItemModal(): void
    {
        $this->showItemModal = false;
        $this->resetItemForm();
    }

    public function resetItemForm(): void
    {
        $this->editingItemId = null;
        $this->editingItemKategoriId = null;
        $this->editingItemParentId = null;
        $this->itemName = '';
        $this->itemUrl = '';
        $this->itemCustomFields = [];
        $this->resetValidation();
    }

    public function addCustomField(): void
    {
        $this->itemCustomFields[] = ['key' => '', 'value' => ''];
    }

    public function removeCustomField(int $index): void
    {
        unset($this->itemCustomFields[$index]);
        $this->itemCustomFields = array_values($this->itemCustomFields);
    }

    public function saveItem(): void
    {
        $validated = $this->validate([
            'itemName' => 'required|string|max:255',
            'itemUrl' => 'nullable|url|max:2048',
            'editingItemKategoriId' => 'required|exists:hjelpemiddel_kategorier,id',
            'editingItemParentId' => 'nullable|exists:hjelpemidler,id',
            'itemCustomFields' => 'array',
            'itemCustomFields.*.key' => 'required|string|max:255',
            'itemCustomFields.*.value' => 'required|string|max:1000',
        ], [
            'itemName.required' => 'Navn er påkrevd.',
            'itemUrl.url' => 'URL må være en gyldig lenke.',
            'editingItemKategoriId.required' => 'Kategori er påkrevd.',
            'editingItemKategoriId.exists' => 'Valgt kategori finnes ikke.',
            'itemCustomFields.*.key.required' => 'Feltnavn er påkrevd.',
            'itemCustomFields.*.value.required' => 'Feltverdi er påkrevd.',
        ]);

        // Filter out empty custom fields
        $customFields = array_values(array_filter(
            $this->itemCustomFields,
            fn ($field) => ! empty($field['key']) && ! empty($field['value'])
        ));

        $data = [
            'hjelpemiddel_kategori_id' => $validated['editingItemKategoriId'],
            'parent_id' => $validated['editingItemParentId'],
            'name' => $validated['itemName'],
            'url' => $validated['itemUrl'] ?: null,
            'custom_fields' => $customFields ?: null,
        ];

        if ($this->editingItemId) {
            $item = Hjelpemiddel::find($this->editingItemId);
            $item?->update($data);
            $this->dispatch('toast', type: 'success', message: 'Hjelpemiddelet ble oppdatert');
        } else {
            // Calculate sort_order based on siblings (same parent)
            $maxSortOrder = Hjelpemiddel::where('hjelpemiddel_kategori_id', $validated['editingItemKategoriId'])
                ->where('parent_id', $validated['editingItemParentId'])
                ->max('sort_order') ?? 0;
            $data['sort_order'] = $maxSortOrder + 1;

            Hjelpemiddel::create($data);
            $this->dispatch('toast', type: 'success', message: 'Hjelpemiddelet ble lagt til');
        }

        $this->closeItemModal();
        unset($this->kategorier);
    }

    public function deleteItem(int $id): void
    {
        Hjelpemiddel::destroy($id);
        unset($this->kategorier);
        $this->dispatch('toast', type: 'success', message: 'Hjelpemiddelet ble slettet');
    }

    public function openKategoriModal(?int $id = null): void
    {
        $this->resetKategoriForm();
        $this->editingKategoriId = $id;

        if ($id) {
            $kategori = HjelpemiddelKategori::find($id);
            if ($kategori) {
                $this->kategoriName = $kategori->name;
            }
        }

        $this->showKategoriModal = true;
    }

    public function closeKategoriModal(): void
    {
        $this->showKategoriModal = false;
        $this->resetKategoriForm();
    }

    public function resetKategoriForm(): void
    {
        $this->editingKategoriId = null;
        $this->kategoriName = '';
        $this->resetValidation();
    }

    public function saveKategori(): void
    {
        $validated = $this->validate([
            'kategoriName' => 'required|string|max:255',
        ], [
            'kategoriName.required' => 'Kategorinavn er påkrevd.',
        ]);

        if ($this->editingKategoriId) {
            $kategori = HjelpemiddelKategori::find($this->editingKategoriId);
            $kategori?->update(['name' => $validated['kategoriName']]);
            $this->dispatch('toast', type: 'success', message: 'Kategorien ble oppdatert');
        } else {
            $maxSortOrder = HjelpemiddelKategori::max('sort_order') ?? 0;

            HjelpemiddelKategori::create([
                'name' => $validated['kategoriName'],
                'sort_order' => $maxSortOrder + 1,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Kategorien ble opprettet');
        }

        $this->closeKategoriModal();
        unset($this->kategorier);
    }

    public function deleteKategori(int $id): void
    {
        $kategori = HjelpemiddelKategori::find($id);
        if ($kategori) {
            $kategori->hjelpemidler()->delete();
            $kategori->delete();
            $this->dispatch('toast', type: 'success', message: 'Kategorien ble slettet');
        }
        unset($this->kategorier);
    }

    public function updateKategoriOrder(string $item, int $position): void
    {
        [$type, $id] = explode('-', $item);

        if ($type !== 'kategori') {
            return;
        }

        $kategorier = HjelpemiddelKategori::orderBy('sort_order')->get();
        $movedIndex = $kategorier->search(fn ($k) => $k->id === (int) $id);

        if ($movedIndex === false) {
            return;
        }

        $moved = $kategorier->pull($movedIndex);
        $kategorier = $kategorier->values();
        $kategorier->splice($position, 0, [$moved]);

        foreach ($kategorier as $index => $kategori) {
            $kategori->sort_order = $index;
            $kategori->save();
        }

        unset($this->kategorier);
    }

    public function updateItemOrder(int $kategoriId, string $item, int $position, ?int $parentId = null): void
    {
        [$type, $id] = explode('-', $item);

        if ($type !== 'item') {
            return;
        }

        $items = Hjelpemiddel::where('hjelpemiddel_kategori_id', $kategoriId)
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->get();

        $movedIndex = $items->search(fn ($i) => $i->id === (int) $id);

        if ($movedIndex === false) {
            return;
        }

        $moved = $items->pull($movedIndex);
        $items = $items->values();
        $items->splice($position, 0, [$moved]);

        foreach ($items as $index => $hjelpemiddel) {
            $hjelpemiddel->sort_order = $index;
            $hjelpemiddel->save();
        }

        unset($this->kategorier);
    }

    public function openBrukerpassModal(): void
    {
        $this->brukerpassValue = $this->brukerpass;
        $this->showBrukerpassModal = true;
    }

    public function closeBrukerpassModal(): void
    {
        $this->showBrukerpassModal = false;
        $this->brukerpassValue = '';
    }

    public function saveBrukerpass(): void
    {
        $this->validate([
            'brukerpassValue' => 'nullable|string|max:50',
        ]);

        Setting::set('hjelpemidler_brukerpass', $this->brukerpassValue);
        unset($this->brukerpass);
        $this->closeBrukerpassModal();
        $this->dispatch('toast', type: 'success', message: 'Brukerpass ble oppdatert');
    }

    public function render()
    {
        return view('livewire.medical.hjelpemidler');
    }
}
