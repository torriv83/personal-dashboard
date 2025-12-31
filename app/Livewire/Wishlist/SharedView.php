<?php

declare(strict_types=1);

namespace App\Livewire\Wishlist;

use App\Models\WishlistGroup;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.shared')]
class SharedView extends Component
{
    public WishlistGroup $group;

    public function mount(string $token): void
    {
        $this->group = WishlistGroup::query()
            ->sharedWithToken($token)
            ->with(['items' => fn ($q) => $q->orderBy('created_at')])
            ->firstOrFail();
    }

    /**
     * @return Collection<int, \App\Models\WishlistItem>
     */
    #[Computed]
    public function items(): Collection
    {
        return $this->group->items;
    }

    #[Computed]
    public function total(): int
    {
        return $this->group->items->sum(fn ($item) => $item->price * $item->quantity);
    }

    public function render()
    {
        return view('livewire.wishlist.shared-view');
    }
}
