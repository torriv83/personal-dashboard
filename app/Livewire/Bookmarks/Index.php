<?php

namespace App\Livewire\Bookmarks;

use App\Jobs\CheckDeadBookmarksJob;
use App\Models\Bookmark;
use App\Models\BookmarkFolder;
use App\Services\OpenGraphService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Collection<int, Bookmark> $bookmarks
 * @property-read Collection<int, BookmarkFolder> $folders
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    // Search and filter
    #[Url]
    public string $search = '';

    #[Url]
    public ?int $folderId = null;

    #[Url]
    public string $sortBy = 'newest';

    // Bookmark modal state
    public bool $showBookmarkModal = false;

    public ?int $editingBookmarkId = null;

    public string $bookmarkUrl = '';

    public string $bookmarkTitle = '';

    public string $bookmarkDescription = '';

    public ?int $bookmarkFolderId = null;

    public bool $fetchingMetadata = false;

    // Folder modal state
    public bool $showFolderModal = false;

    public ?int $editingFolderId = null;

    public string $folderName = '';

    public bool $folderIsDefault = false;

    // Bulk selection
    /** @var array<int, int> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    // Move modal
    public bool $showMoveModal = false;

    public ?int $moveToFolderId = null;

    /**
     * Get all bookmarks, filtered and sorted.
     *
     * @return Collection<int, Bookmark>
     */
    #[Computed]
    public function bookmarks(): Collection
    {
        $query = Bookmark::query()->with('folder');

        // Search
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('url', 'like', '%'.$this->search.'%');
            });
        }

        // Filter by folder
        if ($this->folderId !== null) {
            $query->where('folder_id', $this->folderId);
        }

        // Sort
        $query = match ($this->sortBy) {
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            default => $query->orderBy('created_at', 'desc'), // newest
        };

        return $query->get();
    }

    /**
     * Get all folders.
     *
     * @return Collection<int, BookmarkFolder>
     */
    #[Computed]
    public function folders(): Collection
    {
        return BookmarkFolder::query()
            ->withCount('bookmarks')
            ->orderBy('sort_order')
            ->get();
    }

    // ====================
    // Bookmark CRUD
    // ====================

    public function openBookmarkModal(?int $id = null): void
    {
        $this->resetBookmarkForm();

        if ($id !== null) {
            $bookmark = Bookmark::findOrFail($id);
            $this->editingBookmarkId = $bookmark->id;
            $this->bookmarkUrl = $bookmark->url;
            $this->bookmarkTitle = $bookmark->title;
            $this->bookmarkDescription = $bookmark->description ?? '';
            $this->bookmarkFolderId = $bookmark->folder_id;
        } else {
            // Set default folder if available
            $defaultFolder = BookmarkFolder::getDefault();
            $this->bookmarkFolderId = $defaultFolder?->id;
        }

        $this->showBookmarkModal = true;
    }

    public function closeBookmarkModal(): void
    {
        $this->showBookmarkModal = false;
        $this->resetBookmarkForm();
    }

    public function resetBookmarkForm(): void
    {
        $this->editingBookmarkId = null;
        $this->bookmarkUrl = '';
        $this->bookmarkTitle = '';
        $this->bookmarkDescription = '';
        $this->bookmarkFolderId = null;
        $this->resetErrorBag();
    }

    public function fetchMetadata(): void
    {
        $this->validate([
            'bookmarkUrl' => ['required', 'url'],
        ], [
            'bookmarkUrl.required' => 'URL er påkrevd.',
            'bookmarkUrl.url' => 'Ugyldig URL-format.',
        ]);

        $this->fetchingMetadata = true;

        try {
            $service = app(OpenGraphService::class);

            // Fetch HTML content
            $html = @file_get_contents($this->bookmarkUrl, false, stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'header' => 'User-Agent: Mozilla/5.0 (compatible; PersonalDashboard/1.0)',
                ],
            ]));

            if ($html !== false) {
                // Extract title from HTML
                if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
                    $this->bookmarkTitle = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
                }

                // Try to get description from meta tags
                if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/is', $html, $matches)) {
                    $this->bookmarkDescription = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
                } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\'][^>]*>/is', $html, $matches)) {
                    $this->bookmarkDescription = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
                } elseif (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/is', $html, $matches)) {
                    $this->bookmarkDescription = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
                }
            }

            if ($this->bookmarkTitle === '') {
                $this->bookmarkTitle = $this->bookmarkUrl;
            }

            $this->dispatch('toast', type: 'success', message: 'Metadata hentet!');
        } catch (\Exception $e) {
            $this->bookmarkTitle = $this->bookmarkUrl;
            $this->dispatch('toast', type: 'warning', message: 'Kunne ikke hente metadata, bruker URL som tittel.');
        } finally {
            $this->fetchingMetadata = false;
        }
    }

    public function saveBookmark(): void
    {
        $this->validate([
            'bookmarkUrl' => ['required', 'url'],
            'bookmarkTitle' => ['required', 'string', 'max:255'],
            'bookmarkDescription' => ['nullable', 'string'],
            'bookmarkFolderId' => ['nullable', 'exists:bookmark_folders,id'],
        ], [
            'bookmarkUrl.required' => 'URL er påkrevd.',
            'bookmarkUrl.url' => 'Ugyldig URL-format.',
            'bookmarkTitle.required' => 'Tittel er påkrevd.',
            'bookmarkTitle.max' => 'Tittelen kan ikke være lengre enn 255 tegn.',
        ]);

        // Duplicate check
        $existing = Bookmark::where('url', $this->bookmarkUrl)->first();
        if ($existing !== null && $existing->id !== $this->editingBookmarkId) {
            $this->addError('bookmarkUrl', 'Denne URLen finnes allerede i bokmerker.');

            return;
        }

        if ($this->editingBookmarkId !== null) {
            $bookmark = Bookmark::findOrFail($this->editingBookmarkId);
            $bookmark->update([
                'url' => $this->bookmarkUrl,
                'title' => $this->bookmarkTitle,
                'description' => $this->bookmarkDescription ?: null,
                'folder_id' => $this->bookmarkFolderId,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Bokmerke oppdatert!');
        } else {
            $maxSortOrder = Bookmark::max('sort_order') ?? 0;
            Bookmark::create([
                'url' => $this->bookmarkUrl,
                'title' => $this->bookmarkTitle,
                'description' => $this->bookmarkDescription ?: null,
                'folder_id' => $this->bookmarkFolderId,
                'sort_order' => $maxSortOrder + 1,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Bokmerke opprettet!');
        }

        $this->closeBookmarkModal();
        unset($this->bookmarks);
    }

    public function deleteBookmark(int $id): void
    {
        Bookmark::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Bokmerke slettet!');
        unset($this->bookmarks);
    }

    public function toggleRead(int $id): void
    {
        $bookmark = Bookmark::findOrFail($id);
        $bookmark->update(['is_read' => ! $bookmark->is_read]);
        unset($this->bookmarks);
    }

    public function moveToFolder(int $bookmarkId, ?int $folderId): void
    {
        $bookmark = Bookmark::findOrFail($bookmarkId);
        $bookmark->update(['folder_id' => $folderId]);
        $this->dispatch('toast', type: 'success', message: 'Bokmerke flyttet!');
        unset($this->bookmarks);
        unset($this->folders);
    }

    // ====================
    // Folder CRUD
    // ====================

    public function openFolderModal(?int $id = null): void
    {
        $this->resetFolderForm();

        if ($id !== null) {
            $folder = BookmarkFolder::findOrFail($id);
            $this->editingFolderId = $folder->id;
            $this->folderName = $folder->name;
            $this->folderIsDefault = $folder->is_default;
        }

        $this->showFolderModal = true;
    }

    public function closeFolderModal(): void
    {
        $this->showFolderModal = false;
        $this->resetFolderForm();
    }

    public function resetFolderForm(): void
    {
        $this->editingFolderId = null;
        $this->folderName = '';
        $this->folderIsDefault = false;
        $this->resetErrorBag();
    }

    public function saveFolder(): void
    {
        $this->validate([
            'folderName' => ['required', 'string', 'max:255'],
        ], [
            'folderName.required' => 'Mappenavn er påkrevd.',
            'folderName.max' => 'Mappenavnet kan ikke være lengre enn 255 tegn.',
        ]);

        if ($this->editingFolderId !== null) {
            $folder = BookmarkFolder::findOrFail($this->editingFolderId);
            $folder->update(['name' => $this->folderName]);

            if ($this->folderIsDefault) {
                $folder->setAsDefault();
            } elseif ($folder->is_default) {
                $folder->update(['is_default' => false]);
            }

            $this->dispatch('toast', type: 'success', message: 'Mappe oppdatert!');
        } else {
            $maxSortOrder = BookmarkFolder::max('sort_order') ?? 0;
            $folder = BookmarkFolder::create([
                'name' => $this->folderName,
                'sort_order' => $maxSortOrder + 1,
            ]);

            if ($this->folderIsDefault) {
                $folder->setAsDefault();
            }

            $this->dispatch('toast', type: 'success', message: 'Mappe opprettet!');
        }

        $this->closeFolderModal();
        unset($this->folders);
    }

    public function deleteFolder(int $id): void
    {
        $folder = BookmarkFolder::findOrFail($id);

        // Move all bookmarks to no folder
        Bookmark::where('folder_id', $id)->update(['folder_id' => null]);

        $folder->delete();
        $this->dispatch('toast', type: 'success', message: 'Mappe slettet! Bokmerker er nå uten mappe.');
        unset($this->folders);
        unset($this->bookmarks);
    }

    public function deleteFolderWithBookmarks(int $id): void
    {
        $folder = BookmarkFolder::findOrFail($id);

        // Delete all bookmarks in the folder
        Bookmark::where('folder_id', $id)->delete();

        $folder->delete();
        $this->dispatch('toast', type: 'success', message: 'Mappe og alle bokmerker slettet!');
        unset($this->folders);
        unset($this->bookmarks);
    }

    // ====================
    // Bulk operations
    // ====================

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedIds = $this->bookmarks->pluck('id')->toArray();
        } else {
            $this->selectedIds = [];
        }
    }

    public function openMoveModal(): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('toast', type: 'warning', message: 'Velg minst ett bokmerke først.');

            return;
        }
        $this->showMoveModal = true;
    }

    public function closeMoveModal(): void
    {
        $this->showMoveModal = false;
        $this->moveToFolderId = null;
    }

    public function bulkMove(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        Bookmark::whereIn('id', $this->selectedIds)
            ->update(['folder_id' => $this->moveToFolderId]);

        $count = count($this->selectedIds);
        $this->dispatch('toast', type: 'success', message: "{$count} bokmerker flyttet!");

        $this->selectedIds = [];
        $this->selectAll = false;
        $this->closeMoveModal();
        unset($this->bookmarks);
        unset($this->folders);
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        $count = count($this->selectedIds);
        Bookmark::whereIn('id', $this->selectedIds)->delete();

        $this->dispatch('toast', type: 'success', message: "{$count} bokmerker slettet!");

        $this->selectedIds = [];
        $this->selectAll = false;
        unset($this->bookmarks);
    }

    // ====================
    // Dead link check
    // ====================

    public function checkDeadLinks(): void
    {
        CheckDeadBookmarksJob::dispatch();
        $this->dispatch('toast', type: 'info', message: 'Sjekker lenker i bakgrunnen...');
    }

    public function checkSingleDeadLink(int $id): void
    {
        CheckDeadBookmarksJob::dispatch($id);
        $this->dispatch('toast', type: 'info', message: 'Sjekker lenke...');
    }

    public function clearDeadStatus(int $id): void
    {
        $bookmark = Bookmark::findOrFail($id);
        $bookmark->update(['is_dead' => false]);
        unset($this->bookmarks);
        $this->dispatch('toast', type: 'success', message: 'Status fjernet.');
    }

    // ====================
    // Move to wishlist
    // ====================

    public function moveToWishlist(int $id): void
    {
        $bookmark = Bookmark::findOrFail($id);

        // Dispatch event to wishlist module to open modal with pre-filled data
        $this->dispatch('open-wishlist-modal', [
            'url' => $bookmark->url,
            'name' => $bookmark->title,
            'notes' => $bookmark->description,
        ]);
    }

    // ====================
    // Sorting
    // ====================

    public function setSort(string $sortBy): void
    {
        $this->sortBy = $sortBy;
        unset($this->bookmarks);
    }

    public function setFolderFilter(?int $folderId): void
    {
        $this->folderId = $folderId;
        $this->selectedIds = [];
        $this->selectAll = false;
        unset($this->bookmarks);
    }

    public function clearSearch(): void
    {
        $this->search = '';
        unset($this->bookmarks);
    }

    // ====================
    // Drag and drop
    // ====================

    public function updateOrder(string $item, int $position): void
    {
        // Format: "bookmark-{id}"
        if (! str_starts_with($item, 'bookmark-')) {
            return;
        }

        $id = (int) str_replace('bookmark-', '', $item);

        $bookmarks = Bookmark::query()
            ->when($this->folderId !== null, fn ($q) => $q->where('folder_id', $this->folderId))
            ->when($this->folderId === null, fn ($q) => $q->whereNull('folder_id'))
            ->orderBy('sort_order')
            ->get();

        $movedBookmark = $bookmarks->firstWhere('id', $id);
        if ($movedBookmark === null) {
            return;
        }

        $filtered = $bookmarks->filter(fn ($b) => $b->id !== $id)->values();
        $filtered->splice($position, 0, [$movedBookmark]);

        foreach ($filtered as $index => $bookmark) {
            if ($bookmark->sort_order !== $index) {
                $bookmark->update(['sort_order' => $index]);
            }
        }

        unset($this->bookmarks);
    }

    public function render()
    {
        return view('livewire.bookmarks.index');
    }
}
