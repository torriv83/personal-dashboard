<?php

namespace App\Console\Commands;

use App\Models\Bookmark;
use App\Models\BookmarkFolder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

class ImportLinkwardenBookmarks extends Command
{
    protected $signature = 'bookmarks:import-linkwarden {file : Path to Linkwarden backup.json} {--force : Skip confirmation}';

    protected $description = 'Import bookmarks from a Linkwarden JSON export';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            error("Filen finnes ikke: {$file}");

            return self::FAILURE;
        }

        $json = json_decode(file_get_contents($file), true);

        if (! $json || ! isset($json['collections'])) {
            error('Ugyldig Linkwarden-eksport');

            return self::FAILURE;
        }

        // Build collection paths
        $collections = collect($json['collections'])->keyBy('id');
        $collectionPaths = [];

        foreach ($collections as $id => $c) {
            $path = $c['name'];
            $parentId = $c['parentId'] ?? null;

            while ($parentId && isset($collections[$parentId])) {
                $parent = $collections[$parentId];
                $path = $parent['name'].' > '.$path;
                $parentId = $parent['parentId'] ?? null;
            }

            $collectionPaths[$id] = $path;
        }

        // Count stats
        $skipFolders = ['Privat > Ønskeliste'];
        $totalLinks = 0;
        $skippedLinks = 0;

        foreach ($collections as $id => $c) {
            $linkCount = count($c['links'] ?? []);
            if (in_array($collectionPaths[$id], $skipFolders)) {
                $skippedLinks += $linkCount;
            } else {
                $totalLinks += $linkCount;
            }
        }

        $pinnedCount = count($json['pinnedLinks'] ?? []);

        // Show summary
        info('Linkwarden Import');
        $this->newLine();

        table(
            ['Type', 'Antall'],
            [
                ['Mapper (collections)', (string) count($collections)],
                ['Bokmerker i mapper', (string) $totalLinks],
                ['Pinned links', (string) $pinnedCount],
                ['Hoppes over (Ønskeliste)', (string) $skippedLinks],
            ]
        );

        $this->newLine();

        if (! $this->option('force') && ! confirm('Vil du importere disse bokmerkene?', true)) {
            warning('Import avbrutt');

            return self::SUCCESS;
        }

        // Start import
        DB::beginTransaction();

        try {
            // Create folders
            $folderMap = [];
            $sortOrder = BookmarkFolder::max('sort_order') ?? 0;
            $foldersCreated = 0;

            foreach ($collectionPaths as $collectionId => $path) {
                if (in_array($path, $skipFolders)) {
                    continue;
                }

                $folder = BookmarkFolder::where('name', $path)->first();
                if (! $folder) {
                    $sortOrder++;
                    $folder = BookmarkFolder::create([
                        'name' => $path,
                        'sort_order' => $sortOrder,
                    ]);
                    $foldersCreated++;
                }
                $folderMap[$collectionId] = $folder->id;
            }

            // Import bookmarks
            $imported = 0;
            $duplicates = 0;
            $bookmarkSortOrder = Bookmark::max('sort_order') ?? 0;

            foreach ($collections as $collectionId => $c) {
                $path = $collectionPaths[$collectionId];

                if (in_array($path, $skipFolders)) {
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

            $this->newLine();
            info('Import fullført!');
            table(
                ['Resultat', 'Antall'],
                [
                    ['Mapper opprettet', (string) $foldersCreated],
                    ['Bokmerker importert', (string) $imported],
                    ['Duplikater hoppet over', (string) $duplicates],
                ]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            error('Import feilet: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
