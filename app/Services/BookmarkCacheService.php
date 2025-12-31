<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bookmark;
use App\Models\BookmarkFolder;
use App\Models\BookmarkTag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BookmarkCacheService
{
    private const CACHE_TAG = 'bookmarks';

    private const CACHE_TTL = 60 * 60 * 24; // 24 hours

    /**
     * Get all bookmarks (cached).
     *
     * @return Collection<int, Bookmark>
     */
    public function getAllBookmarks(): Collection
    {
        return Cache::tags([self::CACHE_TAG])->remember(
            'bookmarks.all',
            self::CACHE_TTL,
            fn () => Bookmark::query()
                ->with(['folder', 'tags'])
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    /**
     * Get all folders with bookmark counts (cached).
     *
     * @return Collection<int, BookmarkFolder>
     */
    public function getFolders(): Collection
    {
        return Cache::tags([self::CACHE_TAG])->remember(
            'bookmarks.folders',
            self::CACHE_TTL,
            fn () => BookmarkFolder::query()
                ->withCount('bookmarks')
                ->orderBy('sort_order')
                ->get()
        );
    }

    /**
     * Get folder tree with children (cached).
     *
     * @return Collection<int, BookmarkFolder>
     */
    public function getFolderTree(): Collection
    {
        return Cache::tags([self::CACHE_TAG])->remember(
            'bookmarks.folder_tree',
            self::CACHE_TTL,
            fn () => BookmarkFolder::query()
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->withCount('bookmarks')->orderBy('sort_order')])
                ->withCount('bookmarks')
                ->orderBy('sort_order')
                ->get()
        );
    }

    /**
     * Get root folders (cached).
     *
     * @return Collection<int, BookmarkFolder>
     */
    public function getRootFolders(): Collection
    {
        return Cache::tags([self::CACHE_TAG])->remember(
            'bookmarks.root_folders',
            self::CACHE_TTL,
            fn () => BookmarkFolder::query()
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get()
        );
    }

    /**
     * Get all tags with bookmark counts (cached).
     *
     * @return Collection<int, BookmarkTag>
     */
    public function getTags(): Collection
    {
        return Cache::tags([self::CACHE_TAG])->remember(
            'bookmarks.tags',
            self::CACHE_TTL,
            fn () => BookmarkTag::query()
                ->withCount('bookmarks')
                ->orderBy('sort_order')
                ->get()
        );
    }

    /**
     * Clear all bookmark-related caches.
     */
    public function clearAll(): void
    {
        Cache::tags([self::CACHE_TAG])->flush();
    }

    /**
     * Clear only bookmark cache (not folders/tags).
     */
    public function clearBookmarks(): void
    {
        Cache::tags([self::CACHE_TAG])->forget('bookmarks.all');
    }

    /**
     * Clear only folder cache.
     */
    public function clearFolders(): void
    {
        Cache::tags([self::CACHE_TAG])->forget('bookmarks.folders');
        Cache::tags([self::CACHE_TAG])->forget('bookmarks.folder_tree');
        Cache::tags([self::CACHE_TAG])->forget('bookmarks.root_folders');
    }

    /**
     * Clear only tag cache.
     */
    public function clearTags(): void
    {
        Cache::tags([self::CACHE_TAG])->forget('bookmarks.tags');
    }
}
