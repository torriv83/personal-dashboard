<?php

declare(strict_types=1);

namespace App\Livewire\Bookmarks;

use App\Models\Bookmark;
use App\Models\BookmarkFolder;
use App\Models\User;
use App\Services\BookmarkCacheService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.guest', ['manifest' => '/manifests/bokmerker.json'])]
class QuickAdd extends Component
{
    private BookmarkCacheService $cacheService;

    public function boot(BookmarkCacheService $cacheService): void
    {
        $this->cacheService = $cacheService;
    }

    public ?User $user = null;

    public string $url = '';

    public string $title = '';

    public string $description = '';

    public ?int $folderId = null;

    public bool $isLoading = false;

    public bool $isSaved = false;

    public ?string $duplicateUrl = null;

    // Folder search
    public string $searchFolder = '';

    // Folder modal
    public bool $showFolderModal = false;

    public ?int $editingFolderId = null;

    public string $folderName = '';

    public ?int $folderParentId = null;

    public bool $folderIsDefault = false;

    public function mount(): void
    {
        // Get parameters from request query string
        $token = request()->query('token');
        $url = request()->query('url');
        $title = request()->query('title');
        $text = request()->query('text');

        // Try token authentication first (for bookmarklet)
        if ($token) {
            $this->user = User::findByBookmarkToken($token);
        }

        // Fall back to session authentication (for PWA share target)
        if (! $this->user && Auth::check()) {
            $this->user = Auth::user();
        }

        // No valid authentication
        if (! $this->user) {
            abort(404);
        }

        // Set URL from query params
        if ($url) {
            $this->url = $url;
        }

        // Set title from query params
        if ($title) {
            $this->title = $title;
        }

        // Use text as description if provided (from share target)
        if ($text && ! $url) {
            // Sometimes text contains URL
            if (filter_var($text, FILTER_VALIDATE_URL)) {
                $this->url = $text;
            } else {
                $this->description = $text;
            }
        }

        // Set default folder
        $defaultFolder = BookmarkFolder::getDefault();
        if ($defaultFolder) {
            $this->folderId = $defaultFolder->id;
        }

        // Auto-fetch metadata if URL is provided but title is empty
        if ($this->url && ! $this->title) {
            $this->fetchMetadata();
        }
    }

    /**
     * @return Collection<int, BookmarkFolder>
     */
    #[Computed]
    public function folders(): Collection
    {
        return BookmarkFolder::orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * Get folder tree with parent-child relationships
     *
     * @return array<int, array{folder: BookmarkFolder, children: Collection<int, BookmarkFolder>}>
     */
    #[Computed]
    public function folderTree(): array
    {
        $allFolders = $this->folders();

        // Get only root folders (no parent)
        $rootFolders = $allFolders->whereNull('parent_id')->values();

        // Build tree structure using arrays instead of dynamic properties
        return $rootFolders->map(fn (BookmarkFolder $folder) => [
            'folder' => $folder,
            'children' => $allFolders->where('parent_id', $folder->id)->values(),
        ])->all();
    }

    /**
     * Auto-select matching folder when search changes
     */
    public function updatedSearchFolder(): void
    {
        if ($this->searchFolder) {
            $match = BookmarkFolder::where('name', 'like', '%' . $this->searchFolder . '%')->first();
            if ($match) {
                $this->folderId = $match->id;
            }
        }
    }

    public function fetchMetadata(): void
    {
        if (! $this->url || ! filter_var($this->url, FILTER_VALIDATE_URL)) {
            return;
        }

        $this->isLoading = true;

        try {
            // Fetch HTML content
            $html = @file_get_contents($this->url, false, stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'header' => 'User-Agent: Mozilla/5.0 (compatible; PersonalDashboard/1.0)',
                ],
            ]));

            if ($html !== false) {
                // Extract title from HTML
                if (! $this->title && preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
                    $this->title = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
                }

                // Try to get description from meta tags
                if (! $this->description) {
                    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/is', $html, $matches)) {
                        $this->description = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
                    } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\'][^>]*>/is', $html, $matches)) {
                        $this->description = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
                    } elseif (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/is', $html, $matches)) {
                        $this->description = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
                    }
                }
            }

            // Fallback to URL as title
            if ($this->title === '') {
                $this->title = $this->url;
            }
        } catch (\Exception $e) {
            // Ignore errors, user can manually fill in the data
            if ($this->title === '') {
                $this->title = $this->url;
            }
        }

        $this->isLoading = false;
    }

    public function save(): void
    {
        $this->validate([
            'url' => ['required', 'url'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'folderId' => ['nullable', 'exists:bookmark_folders,id'],
        ]);

        // Check for duplicates
        $existingBookmark = Bookmark::where('url', $this->url)->first();
        if ($existingBookmark) {
            $this->duplicateUrl = $this->url;

            return;
        }

        // Get next sort order
        $maxSortOrder = Bookmark::query()
            ->when($this->folderId, fn ($q) => $q->where('folder_id', $this->folderId))
            ->when(! $this->folderId, fn ($q) => $q->whereNull('folder_id'))
            ->max('sort_order') ?? -1;

        Bookmark::create([
            'url' => $this->url,
            'title' => $this->title,
            'description' => $this->description ?: null,
            'folder_id' => $this->folderId,
            'sort_order' => $maxSortOrder + 1,
        ]);

        $this->isSaved = true;
    }

    public function addAnother(): void
    {
        $this->reset(['url', 'title', 'description', 'isSaved', 'duplicateUrl']);
    }

    public function openFolderModal(?int $id = null, ?int $parentId = null): void
    {
        $this->editingFolderId = $id;
        $this->folderParentId = $parentId;

        if ($id) {
            // Edit existing folder
            $folder = BookmarkFolder::find($id);
            if ($folder) {
                $this->folderName = $folder->name;
                $this->folderParentId = $folder->parent_id;
                $this->folderIsDefault = $folder->is_default;
            }
        } else {
            // Create new folder
            $this->folderName = '';
            $this->folderIsDefault = false;
        }

        $this->showFolderModal = true;
    }

    public function saveFolder(): void
    {
        $this->validate([
            'folderName' => ['required', 'string', 'max:255'],
            'folderParentId' => ['nullable', 'exists:bookmark_folders,id'],
        ]);

        // Validate max 2 levels (no subfolder under subfolder)
        if ($this->folderParentId !== null) {
            $parent = BookmarkFolder::find($this->folderParentId);
            if ($parent !== null && $parent->parent_id !== null) {
                $this->addError('folderParentId', 'Kan ikke opprette mappe under en undermappe. Maksimum 2 nivåer.');

                return;
            }
        }

        if ($this->editingFolderId) {
            // Update existing folder
            $folder = BookmarkFolder::find($this->editingFolderId);
            if ($folder) {
                $folder->update([
                    'name' => $this->folderName,
                    'parent_id' => $this->folderParentId,
                ]);

                if ($this->folderIsDefault) {
                    $folder->setAsDefault();
                }
            }
        } else {
            // Create new folder
            $maxSortOrder = BookmarkFolder::query()
                ->when($this->folderParentId, fn ($q) => $q->where('parent_id', $this->folderParentId))
                ->when(! $this->folderParentId, fn ($q) => $q->whereNull('parent_id'))
                ->max('sort_order') ?? -1;

            $folder = BookmarkFolder::create([
                'name' => $this->folderName,
                'parent_id' => $this->folderParentId,
                'sort_order' => $maxSortOrder + 1,
            ]);

            if ($this->folderIsDefault) {
                $folder->setAsDefault();
            }

            // Auto-select the newly created folder
            $this->folderId = $folder->id;
        }

        $this->showFolderModal = false;
        $this->reset(['editingFolderId', 'folderName', 'folderParentId', 'folderIsDefault']);

        // Clear server-side cache so Index component sees the new folder
        $this->cacheService->clearAll();

        // Reset computed properties to refresh folder list
        unset($this->folders, $this->folderTree);
    }

    public function render(): View
    {
        return view('livewire.bookmarks.quick-add');
    }
}
