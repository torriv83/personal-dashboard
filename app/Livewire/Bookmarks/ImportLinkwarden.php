<?php

namespace App\Livewire\Bookmarks;

use App\Models\Bookmark;
use App\Models\BookmarkFolder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class ImportLinkwarden extends Component
{
    use WithFileUploads;

    public $file;

    public bool $isParsed = false;

    public bool $isImported = false;

    /** @var array<string, mixed> */
    public array $stats = [];

    public string $errorMessage = '';

    /** @var array<int, array<string, mixed>> */
    public array $previewFolders = [];

    public function updatedFile(): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:10240'],
        ], [
            'file.required' => 'Velg en fil.',
            'file.mimetypes' => 'Filen må være en JSON-fil.',
            'file.max' => 'Filen kan ikke være større enn 10MB.',
        ]);

        $this->parseFile();
    }

    public function parseFile(): void
    {
        $this->reset(['isParsed', 'isImported', 'stats', 'errorMessage', 'previewFolders']);

        try {
            $content = file_get_contents($this->file->getRealPath());
            $json = json_decode($content, true);

            if (! $json || ! isset($json['collections'])) {
                $this->errorMessage = 'Ugyldig Linkwarden-eksport. Filen må inneholde "collections".';

                return;
            }

            // Build collection hierarchy info
            $collections = collect($json['collections'])->keyBy('id');
            $collectionInfo = [];

            foreach ($collections as $id => $c) {
                // Build full path for display and skip checking
                $path = $c['name'];
                $parentId = $c['parentId'] ?? null;
                $depth = 0;

                while ($parentId && isset($collections[$parentId])) {
                    $parent = $collections[$parentId];
                    $path = $parent['name'].' > '.$path;
                    $parentId = $parent['parentId'] ?? null;
                    $depth++;
                }

                $collectionInfo[$id] = [
                    'name' => $c['name'],
                    'path' => $path,
                    'parentId' => $c['parentId'] ?? null,
                    'depth' => $depth,
                ];
            }

            // Count stats
            $skipFolders = [];
            $totalLinks = 0;
            $skippedLinks = 0;
            $folders = [];

            foreach ($collections as $id => $c) {
                $linkCount = count($c['links'] ?? []);
                $info = $collectionInfo[$id];

                if (in_array($info['path'], $skipFolders)) {
                    $skippedLinks += $linkCount;
                } else {
                    $totalLinks += $linkCount;
                    if ($linkCount > 0) {
                        // Show hierarchical display with depth indicator
                        $displayName = $info['depth'] > 0
                            ? str_repeat('  ', $info['depth']).'↳ '.$info['name']
                            : $info['name'];

                        $folders[] = [
                            'name' => $displayName,
                            'path' => $info['path'],
                            'count' => $linkCount,
                        ];
                    }
                }
            }

            $pinnedCount = count($json['pinnedLinks'] ?? []);

            $this->stats = [
                'collections' => count($collections),
                'bookmarks' => $totalLinks,
                'pinned' => $pinnedCount,
                'skipped' => $skippedLinks,
            ];

            $this->previewFolders = $folders;
            $this->isParsed = true;
        } catch (\Exception $e) {
            $this->errorMessage = 'Kunne ikke lese filen: '.$e->getMessage();
        }
    }

    public function import(): void
    {
        if (! $this->isParsed || ! $this->file) {
            return;
        }

        try {
            $content = file_get_contents($this->file->getRealPath());
            $json = json_decode($content, true);

            $collections = collect($json['collections'])->keyBy('id');

            // Build hierarchy info for each collection
            $collectionInfo = [];
            foreach ($collections as $id => $c) {
                $path = $c['name'];
                $parentId = $c['parentId'] ?? null;
                $ancestors = [];

                // Collect all ancestors
                $tempParentId = $parentId;
                while ($tempParentId && isset($collections[$tempParentId])) {
                    $ancestor = $collections[$tempParentId];
                    $ancestors[] = $tempParentId;
                    $path = $ancestor['name'].' > '.$path;
                    $tempParentId = $ancestor['parentId'] ?? null;
                }

                $collectionInfo[$id] = [
                    'name' => $c['name'],
                    'path' => $path,
                    'linkwardenParentId' => $parentId,
                    'ancestors' => array_reverse($ancestors), // Root first
                    'depth' => count($ancestors),
                ];
            }

            $skipFolders = [];

            DB::beginTransaction();

            // Create folders with proper hierarchy (max 2 levels)
            // Maps Linkwarden collection ID -> our BookmarkFolder ID
            $folderMap = [];
            $sortOrder = BookmarkFolder::max('sort_order') ?? 0;
            $foldersCreated = 0;

            // First pass: Create all root folders (depth 0)
            foreach ($collectionInfo as $collectionId => $info) {
                if (in_array($info['path'], $skipFolders)) {
                    continue;
                }

                if ($info['depth'] === 0) {
                    $folder = BookmarkFolder::where('name', $info['name'])
                        ->whereNull('parent_id')
                        ->first();

                    if (! $folder) {
                        $sortOrder++;
                        $folder = BookmarkFolder::create([
                            'name' => $info['name'],
                            'parent_id' => null,
                            'sort_order' => $sortOrder,
                        ]);
                        $foldersCreated++;
                    }
                    $folderMap[$collectionId] = $folder->id;
                }
            }

            // Second pass: Create child folders (depth 1+)
            // For depth > 1, we flatten to depth 1 (child of the root ancestor)
            foreach ($collectionInfo as $collectionId => $info) {
                if (in_array($info['path'], $skipFolders)) {
                    continue;
                }

                if ($info['depth'] > 0) {
                    // Find the root ancestor (first in ancestors array)
                    $rootAncestorId = $info['ancestors'][0] ?? null;
                    $parentFolderId = $rootAncestorId ? ($folderMap[$rootAncestorId] ?? null) : null;

                    // Check if folder already exists with this name under this parent
                    $folder = BookmarkFolder::where('name', $info['name'])
                        ->where('parent_id', $parentFolderId)
                        ->first();

                    if (! $folder) {
                        $sortOrder++;
                        $folder = BookmarkFolder::create([
                            'name' => $info['name'],
                            'parent_id' => $parentFolderId,
                            'sort_order' => $sortOrder,
                        ]);
                        $foldersCreated++;
                    }
                    $folderMap[$collectionId] = $folder->id;
                }
            }

            // Import bookmarks
            $imported = 0;
            $duplicates = 0;
            $bookmarkSortOrder = Bookmark::max('sort_order') ?? 0;

            foreach ($collections as $collectionId => $c) {
                $info = $collectionInfo[$collectionId];

                if (in_array($info['path'], $skipFolders)) {
                    continue;
                }

                foreach ($c['links'] ?? [] as $link) {
                    if (Bookmark::where('url', $link['url'])->exists()) {
                        $duplicates++;

                        continue;
                    }

                    $bookmarkSortOrder++;
                    Bookmark::create([
                        'url' => $link['url'],
                        'title' => mb_substr($link['name'] ?: $link['url'], 0, 255),
                        'description' => $link['description'] ?: null,
                        'folder_id' => $folderMap[$collectionId] ?? null,
                        'sort_order' => $bookmarkSortOrder,
                        'created_at' => Carbon::parse($link['createdAt']),
                    ]);
                    $imported++;
                }
            }

            // Import pinned links
            foreach ($json['pinnedLinks'] ?? [] as $link) {
                if (Bookmark::where('url', $link['url'])->exists()) {
                    $duplicates++;

                    continue;
                }

                $bookmarkSortOrder++;
                Bookmark::create([
                    'url' => $link['url'],
                    'title' => mb_substr($link['name'] ?: $link['url'], 0, 255),
                    'description' => $link['description'] ?: null,
                    'folder_id' => null,
                    'sort_order' => $bookmarkSortOrder,
                    'created_at' => Carbon::parse($link['createdAt']),
                ]);
                $imported++;
            }

            DB::commit();

            $this->stats['folders_created'] = $foldersCreated;
            $this->stats['imported'] = $imported;
            $this->stats['duplicates'] = $duplicates;
            $this->isImported = true;

            $this->dispatch('toast', type: 'success', message: "{$imported} bokmerker importert!");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errorMessage = 'Import feilet: '.$e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.bookmarks.import-linkwarden');
    }
}
