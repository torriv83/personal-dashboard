<?php

namespace App\Livewire\Bookmarks;

use App\Enums\WishlistStatus;
use App\Models\WishlistGroup;
use App\Models\WishlistItem;
use App\Services\OpenGraphService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class AddToWishlistModal extends Component
{
    public bool $showModal = false;

    public string $itemNavn = '';

    public string $itemUrl = '';

    public string $itemImageUrl = '';

    public int $itemPris = 0;

    public int $itemAntall = 1;

    public string $itemStatus = 'waiting';

    public ?int $groupId = null;

    public string $notes = '';

    public bool $fetchingImage = false;

    /**
     * @return Collection<int, WishlistGroup>
     */
    #[Computed]
    public function groups(): Collection
    {
        return WishlistGroup::orderBy('sort_order')->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return WishlistStatus::options();
    }

    /**
     * @param  array{url?: string, name?: string, notes?: string}  $data
     */
    #[On('open-wishlist-modal')]
    public function openModal(array $data = []): void
    {
        $this->reset();

        $this->itemUrl = $data['url'] ?? '';
        $this->itemNavn = $data['name'] ?? '';
        $this->notes = $data['notes'] ?? '';

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset();
    }

    public function fetchImageFromUrl(): void
    {
        if (! $this->itemUrl) {
            $this->dispatch('toast', type: 'error', message: 'Legg inn en URL først');

            return;
        }

        $this->fetchingImage = true;

        try {
            $openGraphService = app(OpenGraphService::class);
            $fetchedImageUrl = $openGraphService->fetchImage($this->itemUrl);
            if ($fetchedImageUrl) {
                $this->itemImageUrl = $fetchedImageUrl;
            } else {
                $this->dispatch('toast', type: 'error', message: 'Fant ikke bilde på URL');
            }
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Kunne ikke hente bilde');
        }

        $this->fetchingImage = false;
    }

    public function handlePastedImage(string $base64Data): void
    {
        try {
            // Extract mime type and data from base64 string
            if (! preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $base64Data, $matches)) {
                $this->dispatch('toast', type: 'error', message: 'Ugyldig bildeformat');

                return;
            }

            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $base64String = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);

            if ($base64String === null) {
                $this->dispatch('toast', type: 'error', message: 'Kunne ikke dekode bildet');

                return;
            }

            $imageData = base64_decode($base64String, true);

            if ($imageData === false) {
                $this->dispatch('toast', type: 'error', message: 'Kunne ikke dekode bildet');

                return;
            }

            // Check file size (max 5MB)
            if (strlen($imageData) > 5 * 1024 * 1024) {
                $this->dispatch('toast', type: 'error', message: 'Bildet er for stort (maks 5MB)');

                return;
            }

            // Generate unique filename
            $filename = md5(uniqid()).'-'.time().'.'.$extension;
            $filePath = 'wishlist-images/'.$filename;

            // Store the image
            Storage::disk('public')->put($filePath, $imageData);

            // Set the image URL
            $this->itemImageUrl = '/storage/'.$filePath;
            $this->dispatch('toast', type: 'success', message: 'Bilde limt inn');
        } catch (\Exception $e) {
            Log::warning('handlePastedImage error', ['error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Kunne ikke lagre bildet');
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'itemNavn' => 'required|string|max:255',
            'itemUrl' => 'nullable|url|max:2048',
            'itemImageUrl' => 'nullable|string|max:2048',
            'itemPris' => 'required|integer|min:0',
            'itemAntall' => 'required|integer|min:1',
            'itemStatus' => 'required|in:waiting,saving,saved,purchased',
            'groupId' => 'nullable|exists:wishlist_groups,id',
        ], [
            'itemNavn.required' => 'Navn er påkrevd.',
            'itemUrl.url' => 'URL må være en gyldig lenke.',
            'itemPris.required' => 'Pris er påkrevd.',
            'itemPris.min' => 'Pris kan ikke være negativ.',
        ]);

        $openGraphService = app(OpenGraphService::class);
        $imageUrl = null;

        // Process image URL
        if ($validated['itemImageUrl']) {
            if (str_starts_with($validated['itemImageUrl'], '/storage/')) {
                $imageUrl = $validated['itemImageUrl'];
            } else {
                $imageUrl = $openGraphService->downloadAndStoreImage($validated['itemImageUrl']);
            }
        }

        // Auto-fetch image if URL is provided but no image_url
        if (! $imageUrl && $validated['itemUrl']) {
            $fetchedImageUrl = $openGraphService->fetchImage($validated['itemUrl']);
            if ($fetchedImageUrl) {
                $imageUrl = $fetchedImageUrl;
            }
        }

        // Calculate sort_order
        if ($this->groupId) {
            $sortOrder = 0;
        } else {
            $maxSortOrder = WishlistItem::whereNull('group_id')->max('sort_order') ?? 0;
            $maxGroupSortOrder = WishlistGroup::max('sort_order') ?? 0;
            $sortOrder = max($maxSortOrder, $maxGroupSortOrder) + 1;
        }

        WishlistItem::create([
            'name' => $validated['itemNavn'],
            'url' => $validated['itemUrl'] ?: null,
            'image_url' => $imageUrl,
            'price' => $validated['itemPris'],
            'quantity' => $validated['itemAntall'],
            'status' => $validated['itemStatus'],
            'group_id' => $this->groupId,
            'sort_order' => $sortOrder,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Lagt til i ønskelisten');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('livewire.bookmarks.add-to-wishlist-modal');
    }
}
