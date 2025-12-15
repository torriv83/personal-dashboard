<?php

namespace App\Livewire\Wishlist;

use App\Enums\WishlistStatus;
use App\Models\WishlistGroup;
use App\Models\WishlistItem;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * @property-read Collection<int, array{id: int, navn: string, url: ?string, pris: ?int, antall: ?int, status: ?string, prioritet: int, is_group: bool, items: array<int, array{id: int, navn: string, url: ?string, pris: int, antall: int, status: string}>}> $wishlists
 * @property-read int $totalRemaining
 * @property-read int $totalAll
 * @property-read array<int, array{id: int, name: string}> $groups
 * @property-read array<string, string> $statusOptions
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    // Modal states
    public bool $showItemModal = false;

    public bool $showGroupModal = false;

    public bool $showShareModal = false;

    // Share modal state
    public ?int $sharingGroupId = null;

    public ?string $shareUrl = null;

    public bool $sharingEnabled = false;

    // Form data for single item
    public ?int $editingItemId = null;

    public ?int $editingItemGroupId = null;

    public string $itemNavn = '';

    public string $itemUrl = '';

    public int $itemPris = 0;

    public int $itemAntall = 1;

    public string $itemStatus = 'waiting';

    // Form data for group
    public ?int $editingGroupId = null;

    public string $groupNavn = '';

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return WishlistStatus::options();
    }

    /**
     * Get all groups for the move-to-group dropdown.
     *
     * @return array<int, array{id: int, name: string}>
     */
    #[Computed]
    public function groups(): array
    {
        return WishlistGroup::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (WishlistGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
            ])
            ->toArray();
    }

    /**
     * @return Collection<int, array{id: int, navn: string, url: ?string, pris: ?int, antall: ?int, status: ?string, prioritet: int, is_group: bool, items: array<int, array{id: int, navn: string, url: ?string, pris: int, antall: int, status: string}>}>
     */
    #[Computed]
    public function wishlists(): Collection
    {
        // Get all groups with their items, ordered by sort_order
        $groups = WishlistGroup::query()
            ->with(['items' => fn ($query) => $query->orderBy('created_at')])
            ->orderBy('sort_order')
            ->get();

        // Get standalone items (not in any group), ordered by sort_order
        $standaloneItems = WishlistItem::query()
            ->whereNull('group_id')
            ->orderBy('sort_order')
            ->get();

        // Merge and sort by sort_order
        $result = collect();

        // Add groups
        foreach ($groups as $group) {
            $result->push([
                'id' => $group->id,
                'navn' => $group->name,
                'url' => null,
                'pris' => null,
                'antall' => null,
                'status' => null,
                'status_value' => null,
                'prioritet' => $group->sort_order,
                'is_group' => true,
                'is_shared' => $group->is_shared,
                'items' => $group->items->map(fn (WishlistItem $item): array => [
                    'id' => $item->id,
                    'navn' => $item->name,
                    'url' => $item->url,
                    'pris' => $item->price,
                    'antall' => $item->quantity,
                    'status' => $item->status->label(),
                    'status_value' => $item->status->value,
                ])->toArray(),
            ]);
        }

        // Add standalone items
        foreach ($standaloneItems as $item) {
            $result->push([
                'id' => $item->id,
                'navn' => $item->name,
                'url' => $item->url,
                'pris' => $item->price,
                'antall' => $item->quantity,
                'status' => $item->status->label(),
                'status_value' => $item->status->value,
                'prioritet' => $item->sort_order,
                'is_group' => false,
                'items' => [],
            ]);
        }

        // Sort by sort_order (prioritet)
        return $result->sortBy('prioritet')->values();
    }

    #[Computed]
    public function totalRemaining(): int
    {
        $total = 0;
        /** @var array{id: int, navn: string, url: ?string, pris: ?int, antall: ?int, status: ?string, prioritet: int, is_group: bool, items: array<int, array{id: int, navn: string, url: ?string, pris: int, antall: int, status: string}>} $wishlist */
        foreach ($this->wishlists as $wishlist) {
            if ($wishlist['is_group']) {
                foreach ($wishlist['items'] as $item) {
                    if (! in_array($item['status'], ['Spart', 'Kjøpt'])) {
                        $total += $item['pris'] * $item['antall'];
                    }
                }
            } else {
                if (! in_array($wishlist['status'], ['Spart', 'Kjøpt'])) {
                    $total += ($wishlist['pris'] ?? 0) * ($wishlist['antall'] ?? 1);
                }
            }
        }

        return $total;
    }

    #[Computed]
    public function totalAll(): int
    {
        $total = 0;
        /** @var array{id: int, navn: string, url: ?string, pris: ?int, antall: ?int, status: ?string, prioritet: int, is_group: bool, items: array<int, array{id: int, navn: string, url: ?string, pris: int, antall: int, status: string}>} $wishlist */
        foreach ($this->wishlists as $wishlist) {
            if ($wishlist['is_group']) {
                foreach ($wishlist['items'] as $item) {
                    $total += $item['pris'] * $item['antall'];
                }
            } else {
                $total += ($wishlist['pris'] ?? 0) * ($wishlist['antall'] ?? 1);
            }
        }

        return $total;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function getGroupTotal(array $items): int
    {
        return collect($items)->sum(fn ($item) => $item['pris'] * $item['antall']);
    }

    public function mount(): void
    {
        // Open create modal if ?create=1 is in URL
        if (request()->query('create')) {
            $this->openItemModal();
            $this->dispatch('clear-url-params');
        }
    }

    #[On('open-wishlist-modal')]
    public function openItemModal(?int $id = null, ?int $groupId = null): void
    {
        $this->resetItemForm();
        $this->editingItemId = $id;
        $this->editingItemGroupId = $groupId;

        if ($id) {
            $item = WishlistItem::find($id);
            if ($item) {
                $this->itemNavn = $item->name;
                $this->itemUrl = $item->url ?? '';
                $this->itemPris = $item->price;
                $this->itemAntall = $item->quantity;
                $this->itemStatus = $item->status->value;
                $this->editingItemGroupId = $item->group_id;
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
        $this->editingItemGroupId = null;
        $this->itemNavn = '';
        $this->itemUrl = '';
        $this->itemPris = 0;
        $this->itemAntall = 1;
        $this->itemStatus = 'waiting';
        $this->resetValidation();
    }

    public function saveItem(): void
    {
        $validated = $this->validate([
            'itemNavn' => 'required|string|max:255',
            'itemUrl' => 'nullable|url|max:2048',
            'itemPris' => 'required|integer|min:0',
            'itemAntall' => 'required|integer|min:1',
            'itemStatus' => 'required|in:waiting,saving,saved,purchased',
        ], [
            'itemNavn.required' => 'Navn er påkrevd.',
            'itemUrl.url' => 'URL må være en gyldig lenke.',
            'itemPris.required' => 'Pris er påkrevd.',
            'itemPris.min' => 'Pris kan ikke være negativ.',
            'itemAntall.required' => 'Antall er påkrevd.',
            'itemAntall.min' => 'Antall må være minst 1.',
        ]);

        $data = [
            'name' => $validated['itemNavn'],
            'url' => $validated['itemUrl'] ?: null,
            'price' => $validated['itemPris'],
            'quantity' => $validated['itemAntall'],
            'status' => $validated['itemStatus'],
            'group_id' => $this->editingItemGroupId,
        ];

        if ($this->editingItemId) {
            $item = WishlistItem::find($this->editingItemId);
            $item?->update($data);
            $this->dispatch('toast', type: 'success', message: 'Ønsket ble oppdatert');
        } else {
            // Set sort_order for new items
            if ($this->editingItemGroupId) {
                // Items in groups don't need sort_order (sorted by created_at)
                $data['sort_order'] = 0;
            } else {
                // Standalone items get next sort_order
                $maxSortOrder = WishlistItem::whereNull('group_id')->max('sort_order') ?? 0;
                $maxGroupSortOrder = WishlistGroup::max('sort_order') ?? 0;
                $data['sort_order'] = max($maxSortOrder, $maxGroupSortOrder) + 1;
            }

            WishlistItem::create($data);
            $this->dispatch('toast', type: 'success', message: 'Ønsket ble lagt til');
        }

        $this->closeItemModal();
        unset($this->wishlists, $this->totalAll, $this->totalRemaining);
    }

    public function openGroupModal(?int $id = null): void
    {
        $this->resetGroupForm();
        $this->editingGroupId = $id;

        if ($id) {
            $group = WishlistGroup::find($id);
            if ($group) {
                $this->groupNavn = $group->name;
            }
        }

        $this->showGroupModal = true;
    }

    public function closeGroupModal(): void
    {
        $this->showGroupModal = false;
        $this->resetGroupForm();
    }

    public function resetGroupForm(): void
    {
        $this->editingGroupId = null;
        $this->groupNavn = '';
        $this->resetValidation();
    }

    public function saveGroup(): void
    {
        $validated = $this->validate([
            'groupNavn' => 'required|string|max:255',
        ], [
            'groupNavn.required' => 'Gruppenavn er påkrevd.',
        ]);

        if ($this->editingGroupId) {
            $group = WishlistGroup::find($this->editingGroupId);
            $group?->update(['name' => $validated['groupNavn']]);
            $this->dispatch('toast', type: 'success', message: 'Gruppen ble oppdatert');
        } else {
            // Set sort_order for new groups
            $maxSortOrder = WishlistItem::whereNull('group_id')->max('sort_order') ?? 0;
            $maxGroupSortOrder = WishlistGroup::max('sort_order') ?? 0;

            WishlistGroup::create([
                'name' => $validated['groupNavn'],
                'sort_order' => max($maxSortOrder, $maxGroupSortOrder) + 1,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Gruppen ble opprettet');
        }

        $this->closeGroupModal();
        unset($this->wishlists);
    }

    public function deleteItem(int $id): void
    {
        WishlistItem::destroy($id);
        unset($this->wishlists, $this->totalAll, $this->totalRemaining);
        $this->dispatch('toast', type: 'success', message: 'Ønsket ble slettet');
    }

    public function deleteGroup(int $id): void
    {
        $group = WishlistGroup::find($id);
        if ($group) {
            // Delete all items in the group first
            $group->items()->delete();
            $group->delete();
            $this->dispatch('toast', type: 'success', message: 'Gruppen ble slettet');
        }
        unset($this->wishlists, $this->totalAll, $this->totalRemaining);
    }

    public function moveItemToGroup(int $itemId, ?int $groupId): void
    {
        $item = WishlistItem::find($itemId);
        if (! $item) {
            return;
        }

        // If moving to a group, verify the group exists
        if ($groupId !== null) {
            $group = WishlistGroup::find($groupId);
            if (! $group) {
                return;
            }
        }

        // Update the item's group_id
        $item->group_id = $groupId;

        // If moving out of a group (to standalone), assign next sort_order
        if ($groupId === null) {
            $maxSortOrder = WishlistItem::whereNull('group_id')->max('sort_order') ?? 0;
            $maxGroupSortOrder = WishlistGroup::max('sort_order') ?? 0;
            $item->sort_order = max($maxSortOrder, $maxGroupSortOrder) + 1;
        } else {
            // Items in groups don't need sort_order (sorted by created_at)
            $item->sort_order = 0;
        }

        $item->save();

        unset($this->wishlists, $this->totalAll, $this->totalRemaining, $this->groups);

        $message = $groupId ? 'Ønsket ble flyttet til gruppen' : 'Ønsket ble flyttet ut av gruppen';
        $this->dispatch('toast', type: 'success', message: $message);

        // Force a full re-render to update the sortable containers
        $this->dispatch('$refresh');
    }

    public function updateOrder(string $item, int $position): void
    {
        // Parse item key (e.g., "group-2" or "item-3")
        [$type, $id] = explode('-', $item);
        $id = (int) $id;

        // Get all groups and standalone items ordered by sort_order
        $groups = WishlistGroup::orderBy('sort_order')->get();
        $standaloneItems = WishlistItem::whereNull('group_id')->orderBy('sort_order')->get();

        // Combine into a single list
        $allItems = collect();
        foreach ($groups as $group) {
            $allItems->push(['type' => 'group', 'id' => $group->id, 'model' => $group]);
        }
        foreach ($standaloneItems as $standaloneItem) {
            $allItems->push(['type' => 'item', 'id' => $standaloneItem->id, 'model' => $standaloneItem]);
        }
        $allItems = $allItems->sortBy(fn ($i) => $i['model']->sort_order)->values();

        // Find the moved item and remove it from its current position
        $movedIndex = $allItems->search(fn ($i) => $i['type'] === $type && $i['id'] === $id);
        if ($movedIndex === false) {
            return;
        }

        $movedItem = $allItems->pull($movedIndex);
        $allItems = $allItems->values();

        // Insert at new position
        $allItems->splice($position, 0, [$movedItem]);

        // Update sort_order for all items
        foreach ($allItems as $index => $itemData) {
            $itemData['model']->sort_order = $index;
            $itemData['model']->save();
        }

        unset($this->wishlists);
    }

    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'Venter' => 'text-muted-foreground',
            'Begynt å spare' => 'text-yellow-400',
            'Spart', 'Kjøpt' => 'text-accent',
            default => 'text-muted-foreground',
        };
    }

    public function getStatusBgColor(string $status): string
    {
        return match ($status) {
            'Venter' => 'bg-muted-foreground/10',
            'Begynt å spare' => 'bg-yellow-400/10',
            'Spart', 'Kjøpt' => 'bg-accent/10',
            default => 'bg-muted-foreground/10',
        };
    }

    public function updateItemStatus(int $itemId, string $status): void
    {
        $item = WishlistItem::find($itemId);
        if (! $item) {
            return;
        }

        $item->update(['status' => $status]);

        unset($this->wishlists, $this->totalAll, $this->totalRemaining);
        $this->dispatch('toast', type: 'success', message: 'Status oppdatert');
    }

    public function openShareModal(int $groupId): void
    {
        $group = WishlistGroup::find($groupId);
        if (! $group) {
            return;
        }

        $this->sharingGroupId = $groupId;
        $this->sharingEnabled = $group->is_shared;
        $this->shareUrl = $group->getShareUrl();
        $this->showShareModal = true;
    }

    public function closeShareModal(): void
    {
        $this->showShareModal = false;
        $this->sharingGroupId = null;
        $this->shareUrl = null;
        $this->sharingEnabled = false;
    }

    public function toggleSharing(): void
    {
        $group = WishlistGroup::find($this->sharingGroupId);
        if (! $group) {
            return;
        }

        if ($group->is_shared) {
            // Turn off sharing
            $group->update(['is_shared' => false]);
            $this->sharingEnabled = false;
            $this->shareUrl = null;
            $this->dispatch('toast', type: 'success', message: 'Deling er deaktivert');
        } else {
            // Turn on sharing - generate token if needed
            if (! $group->share_token) {
                $group->generateShareToken();
            }
            $group->update(['is_shared' => true]);
            $this->sharingEnabled = true;
            $this->shareUrl = $group->getShareUrl();
            $this->dispatch('toast', type: 'success', message: 'Deling er aktivert');
        }

        unset($this->wishlists);
    }

    public function regenerateShareToken(): void
    {
        $group = WishlistGroup::find($this->sharingGroupId);
        if (! $group || ! $group->is_shared) {
            return;
        }

        $group->generateShareToken();
        $this->shareUrl = $group->getShareUrl();
        $this->dispatch('toast', type: 'success', message: 'Ny delingslenke er generert');
    }

    public function render()
    {
        return view('livewire.wishlist.index');
    }
}
