<?php

namespace App\Livewire\Bookmarks;

use App\Jobs\CheckDeadBookmarksJob;
use App\Models\Bookmark;
use App\Models\BookmarkFolder;
use App\Models\BookmarkTag;
use App\Services\OpenGraphService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read Collection<int, Bookmark> $bookmarks
 * @property-read Collection<int, BookmarkFolder> $folders
 * @property-read Collection<int, BookmarkFolder> $folderTree
 * @property-read Collection<int, BookmarkFolder> $rootFolders
 * @property-read Collection<int, BookmarkFolder> $childFolders
 * @property-read Collection<int, BookmarkTag> $tags
 * @property-read int $totalBookmarksCount
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    private const PER_PAGE = 24;

    // Search and filter
    #[Url]
    public string $search = '';

    #[Url]
    public ?int $folderId = null;

    #[Url]
    public ?int $tagId = null;

    #[Url]
    public string $sortBy = 'newest';

    // Pagination
    public int $limit = self::PER_PAGE;

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

    public ?int $folderParentId = null;

    public bool $folderIsDefault = false;

    // Sidebar state
    /** @var array<int, int> */
    public array $expandedFolders = [];

    // Mobile folder sidebar
    public bool $showMobileFolderSidebar = false;

    // Preview modal state
    public bool $showPreviewModal = false;

    public string $previewUrl = '';

    public string $previewTitle = '';

    // Tag modal state
    public bool $showTagModal = false;

    public ?int $editingTagId = null;

    public string $tagName = '';

    public string $tagColor = '#6366f1';

    /** @var array<int, int> */
    public array $bookmarkTagIds = [];

    // Bulk selection
    /** @var array<int, int> */
    public array $selectedIds = [];

    public bool $selectAll = false;

    // Move modal
    public bool $showMoveModal = false;

    public ?int $moveToFolderId = null;

    /**
     * Build the base query for bookmarks.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Bookmark>
     */
    private function bookmarksQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Bookmark::query()->with(['folder', 'tags']);

        // Search
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhere('url', 'like', '%'.$this->search.'%');
            });
        }

        // Filter by folder OR show only standalone bookmarks
        if ($this->folderId !== null) {
            // Inside a specific folder
            $query->where('folder_id', $this->folderId);
        } elseif ($this->search === '' && $this->tagId === null) {
            // Main view (no search, no tag filter): only show bookmarks WITHOUT folder
            $query->whereNull('folder_id');
        }
        // When searching OR filtering by tag, show all matching bookmarks regardless of folder

        // Filter by tag (works across all folders)
        if ($this->tagId !== null) {
            $query->whereHas('tags', fn ($q) => $q->where('bookmark_tags.id', $this->tagId));
        }

        // Sort
        return match ($this->sortBy) {
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            default => $query->orderBy('created_at', 'desc'), // newest
        };
    }

    /**
     * Get bookmarks with limit applied.
     *
     * @return Collection<int, Bookmark>
     */
    #[Computed]
    public function bookmarks(): Collection
    {
        return $this->bookmarksQuery()->take($this->limit)->get();
    }

    /**
     * Get total count of bookmarks (without limit).
     */
    #[Computed]
    public function totalBookmarksCount(): int
    {
        return $this->bookmarksQuery()->count();
    }

    /**
     * Load more bookmarks.
     */
    public function loadMore(): void
    {
        $this->limit += self::PER_PAGE;
        unset($this->bookmarks);
    }

    /**
     * Check if there are more bookmarks to load.
     */
    public function hasMoreBookmarks(): bool
    {
        return $this->limit < $this->totalBookmarksCount;
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

    /**
     * Get hierarchical folder tree (root folders with children).
     *
     * @return Collection<int, BookmarkFolder>
     */
    #[Computed]
    public function folderTree(): Collection
    {
        return BookmarkFolder::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->withCount('bookmarks')->orderBy('sort_order')])
            ->withCount('bookmarks')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get root folders (for parent dropdown in modal).
     *
     * @return Collection<int, BookmarkFolder>
     */
    #[Computed]
    public function rootFolders(): Collection
    {
        return BookmarkFolder::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get all tags.
     *
     * @return Collection<int, BookmarkTag>
     */
    #[Computed]
    public function tags(): Collection
    {
        return BookmarkTag::query()
            ->withCount('bookmarks')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get the current folder (when inside a folder).
     */
    public function getCurrentFolder(): ?BookmarkFolder
    {
        if ($this->folderId === null) {
            return null;
        }

        return BookmarkFolder::find($this->folderId);
    }

    /**
     * Get child folders of the current folder.
     *
     * @return Collection<int, BookmarkFolder>
     */
    #[Computed]
    public function childFolders(): Collection
    {
        if ($this->folderId === null) {
            return collect();
        }

        return BookmarkFolder::query()
            ->where('parent_id', $this->folderId)
            ->withCount('bookmarks')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Open a folder (navigate into it).
     */
    public function openFolder(int $folderId): void
    {
        $this->folderId = $folderId;
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->limit = self::PER_PAGE;
        $this->showMobileFolderSidebar = false;
        unset($this->bookmarks);
        unset($this->totalBookmarksCount);
    }

    /**
     * Go back to main view (exit folder).
     */
    public function goBack(): void
    {
        $this->folderId = null;
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->limit = self::PER_PAGE;
        unset($this->bookmarks);
        unset($this->totalBookmarksCount);
    }

    /**
     * Toggle folder expanded/collapsed state in sidebar.
     */
    public function toggleFolderExpanded(int $folderId): void
    {
        if (in_array($folderId, $this->expandedFolders, true)) {
            $this->expandedFolders = array_values(array_filter(
                $this->expandedFolders,
                fn ($id) => $id !== $folderId
            ));
        } else {
            $this->expandedFolders[] = $folderId;
        }
    }

    /**
     * Toggle mobile folder sidebar.
     */
    #[\Livewire\Attributes\On('toggleMobileFolderSidebar')]
    public function toggleMobileFolderSidebar(): void
    {
        $this->showMobileFolderSidebar = ! $this->showMobileFolderSidebar;
    }

    /**
     * Close mobile folder sidebar.
     */
    public function closeMobileFolderSidebar(): void
    {
        $this->showMobileFolderSidebar = false;
    }

    // ====================
    // Bookmark CRUD
    // ====================

    #[\Livewire\Attributes\On('openBookmarkModal')]
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
            $this->bookmarkTagIds = $bookmark->tags->pluck('id')->toArray();
        } else {
            // Use current folder, or fall back to default folder
            if ($this->folderId !== null) {
                $this->bookmarkFolderId = $this->folderId;
            } else {
                $defaultFolder = BookmarkFolder::getDefault();
                $this->bookmarkFolderId = $defaultFolder?->id;
            }
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
        $this->bookmarkTagIds = [];
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
            $bookmark->tags()->sync($this->bookmarkTagIds);
            $this->dispatch('toast', type: 'success', message: 'Bokmerke oppdatert!');
        } else {
            $maxSortOrder = Bookmark::max('sort_order') ?? 0;
            $bookmark = Bookmark::create([
                'url' => $this->bookmarkUrl,
                'title' => $this->bookmarkTitle,
                'description' => $this->bookmarkDescription ?: null,
                'folder_id' => $this->bookmarkFolderId,
                'sort_order' => $maxSortOrder + 1,
            ]);
            $bookmark->tags()->sync($this->bookmarkTagIds);
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

    #[\Livewire\Attributes\On('openFolderModal')]
    public function openFolderModal(?int $id = null, ?int $parentId = null): void
    {
        $this->resetFolderForm();

        if ($id !== null) {
            $folder = BookmarkFolder::findOrFail($id);
            $this->editingFolderId = $folder->id;
            $this->folderName = $folder->name;
            $this->folderParentId = $folder->parent_id;
            $this->folderIsDefault = $folder->is_default;
        } elseif ($parentId !== null) {
            // Creating a subfolder under a parent
            $this->folderParentId = $parentId;
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
        $this->folderParentId = null;
        $this->folderIsDefault = false;
        $this->resetErrorBag();
    }

    public function saveFolder(): void
    {
        $this->validate([
            'folderName' => ['required', 'string', 'max:255'],
            'folderParentId' => ['nullable', 'exists:bookmark_folders,id'],
        ], [
            'folderName.required' => 'Mappenavn er påkrevd.',
            'folderName.max' => 'Mappenavnet kan ikke være lengre enn 255 tegn.',
        ]);

        // Validate max 2 levels: parent cannot be a subfolder
        if ($this->folderParentId !== null) {
            $parent = BookmarkFolder::find($this->folderParentId);
            if ($parent !== null && $parent->parent_id !== null) {
                $this->addError('folderParentId', 'Kan ikke opprette mappe under en undermappe (maks 2 nivåer).');

                return;
            }
        }

        if ($this->editingFolderId !== null) {
            $folder = BookmarkFolder::findOrFail($this->editingFolderId);

            // Cannot set parent to self or own children
            if ($this->folderParentId === $folder->id) {
                $this->addError('folderParentId', 'Kan ikke sette mappe som sin egen overordnede.');

                return;
            }

            $folder->update([
                'name' => $this->folderName,
                'parent_id' => $this->folderParentId,
            ]);

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
                'parent_id' => $this->folderParentId,
                'sort_order' => $maxSortOrder + 1,
            ]);

            if ($this->folderIsDefault) {
                $folder->setAsDefault();
            }

            $this->dispatch('toast', type: 'success', message: 'Mappe opprettet!');
        }

        $this->closeFolderModal();
        unset($this->folders);
        unset($this->folderTree);
        unset($this->rootFolders);
    }

    public function deleteFolder(int $id): void
    {
        $folder = BookmarkFolder::findOrFail($id);

        // Move all bookmarks to no folder (including subfolders)
        Bookmark::where('folder_id', $id)->update(['folder_id' => null]);

        // Move bookmarks from child folders to no folder
        $childIds = $folder->children->pluck('id')->toArray();
        if (! empty($childIds)) {
            Bookmark::whereIn('folder_id', $childIds)->update(['folder_id' => null]);
        }

        // Children will be cascade-deleted by the database

        $folder->delete();
        $this->dispatch('toast', type: 'success', message: 'Mappe slettet! Bokmerker er nå uten mappe.');
        unset($this->folders);
        unset($this->folderTree);
        unset($this->rootFolders);
        unset($this->bookmarks);
    }

    public function deleteFolderWithBookmarks(int $id): void
    {
        $folder = BookmarkFolder::findOrFail($id);

        // Delete all bookmarks in the folder
        Bookmark::where('folder_id', $id)->delete();

        // Delete bookmarks in child folders
        $childIds = $folder->children->pluck('id')->toArray();
        if (! empty($childIds)) {
            Bookmark::whereIn('folder_id', $childIds)->delete();
        }

        // Children will be cascade-deleted by the database

        $folder->delete();
        $this->dispatch('toast', type: 'success', message: 'Mappe og alle bokmerker slettet!');
        unset($this->folders);
        unset($this->folderTree);
        unset($this->rootFolders);
        unset($this->bookmarks);
    }

    // ====================
    // Tag CRUD
    // ====================

    #[\Livewire\Attributes\On('openTagModal')]
    public function openTagModal(?int $id = null): void
    {
        $this->resetTagForm();

        if ($id !== null) {
            $tag = BookmarkTag::findOrFail($id);
            $this->editingTagId = $tag->id;
            $this->tagName = $tag->name;
            $this->tagColor = $tag->color;
        }

        $this->showTagModal = true;
    }

    public function closeTagModal(): void
    {
        $this->showTagModal = false;
        $this->resetTagForm();
    }

    public function resetTagForm(): void
    {
        $this->editingTagId = null;
        $this->tagName = '';
        $this->tagColor = '#6366f1';
        $this->resetErrorBag();
    }

    public function saveTag(): void
    {
        $this->validate([
            'tagName' => ['required', 'string', 'max:255'],
            'tagColor' => ['required', 'string', 'max:7'],
        ], [
            'tagName.required' => 'Tagnavn er påkrevd.',
            'tagName.max' => 'Tagnavnet kan ikke være lengre enn 255 tegn.',
        ]);

        if ($this->editingTagId !== null) {
            $tag = BookmarkTag::findOrFail($this->editingTagId);
            $tag->update([
                'name' => $this->tagName,
                'color' => $this->tagColor,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Tag oppdatert!');
        } else {
            $maxSortOrder = BookmarkTag::max('sort_order') ?? 0;
            BookmarkTag::create([
                'name' => $this->tagName,
                'color' => $this->tagColor,
                'sort_order' => $maxSortOrder + 1,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Tag opprettet!');
        }

        $this->closeTagModal();
        unset($this->tags);
    }

    public function deleteTag(int $id): void
    {
        $tag = BookmarkTag::findOrFail($id);
        $tag->delete();
        $this->dispatch('toast', type: 'success', message: 'Tag slettet!');
        $this->closeTagModal();
        unset($this->tags);
        unset($this->bookmarks);
    }

    /**
     * Set tag filter.
     */
    public function setTagFilter(?int $tagId): void
    {
        $this->tagId = $tagId;
        $this->selectedIds = [];
        $this->selectAll = false;
        $this->limit = self::PER_PAGE;
        unset($this->bookmarks);
        unset($this->totalBookmarksCount);
    }

    /**
     * Toggle a tag on/off for a specific bookmark (quick tag from card).
     */
    public function toggleBookmarkTag(int $bookmarkId, int $tagId): void
    {
        $bookmark = Bookmark::findOrFail($bookmarkId);

        if ($bookmark->tags()->where('bookmark_tags.id', $tagId)->exists()) {
            $bookmark->tags()->detach($tagId);
        } else {
            $bookmark->tags()->attach($tagId);
        }

        unset($this->bookmarks);
        unset($this->tags);
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

        // Convert empty string to null for "remove from folder"
        $folderId = $this->moveToFolderId ?: null;

        Bookmark::whereIn('id', $this->selectedIds)
            ->update(['folder_id' => $folderId]);

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

    #[\Livewire\Attributes\On('checkDeadLinks')]
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

        // Also remove the "Død" tag if it exists
        $deadTag = BookmarkTag::where('name', 'Død')->first();
        if ($deadTag) {
            $bookmark->tags()->detach($deadTag->id);
        }

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
        $this->limit = self::PER_PAGE;
        unset($this->bookmarks);
        unset($this->totalBookmarksCount);
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->limit = self::PER_PAGE;
        unset($this->bookmarks);
        unset($this->totalBookmarksCount);
    }

    /**
     * Reset limit when search changes.
     */
    public function updatedSearch(): void
    {
        $this->limit = self::PER_PAGE;
        unset($this->totalBookmarksCount);
    }

    /**
     * Reset limit when sort changes.
     */
    public function updatedSortBy(): void
    {
        $this->limit = self::PER_PAGE;
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

    /**
     * Update folder order (drag and drop in sidebar).
     */
    public function updateFolderOrder(string $item, int $position): void
    {
        // Format: "folder-{id}"
        if (! str_starts_with($item, 'folder-')) {
            return;
        }

        $id = (int) str_replace('folder-', '', $item);
        $folder = BookmarkFolder::find($id);

        if ($folder === null) {
            return;
        }

        // Get folders at the same level (same parent_id)
        $folders = BookmarkFolder::query()
            ->where('parent_id', $folder->parent_id)
            ->orderBy('sort_order')
            ->get();

        $movedFolder = $folders->firstWhere('id', $id);
        if ($movedFolder === null) {
            return;
        }

        $filtered = $folders->filter(fn ($f) => $f->id !== $id)->values();
        $filtered->splice($position, 0, [$movedFolder]);

        foreach ($filtered as $index => $f) {
            if ($f->sort_order !== $index) {
                $f->update(['sort_order' => $index]);
            }
        }

        unset($this->folders);
        unset($this->folderTree);
        unset($this->rootFolders);
    }

    /**
     * Drop bookmark onto folder (drag and drop from grid to sidebar).
     */
    public function dropBookmarkToFolder(int $bookmarkId, ?int $folderId): void
    {
        $bookmark = Bookmark::find($bookmarkId);

        if ($bookmark === null) {
            return;
        }

        // Don't move if already in that folder
        if ($bookmark->folder_id === $folderId) {
            return;
        }

        $bookmark->update(['folder_id' => $folderId]);

        $folderName = $folderId
            ? (BookmarkFolder::find($folderId)->name ?? 'mappe')
            : 'Alle bokmerker';

        $this->dispatch('toast', type: 'success', message: "Flyttet til {$folderName}");

        unset($this->bookmarks);
        unset($this->folders);
        unset($this->folderTree);
        unset($this->rootFolders);
    }

    /**
     * Open preview modal for a bookmark.
     */
    public function openPreview(int $bookmarkId): void
    {
        $bookmark = Bookmark::find($bookmarkId);

        if ($bookmark === null) {
            return;
        }

        $this->previewUrl = $bookmark->url;
        $this->previewTitle = $bookmark->title;
        $this->showPreviewModal = true;
    }

    /**
     * Close preview modal.
     */
    public function closePreview(): void
    {
        $this->showPreviewModal = false;
        $this->previewUrl = '';
        $this->previewTitle = '';
    }

    public function render()
    {
        return view('livewire.bookmarks.index');
    }
}
