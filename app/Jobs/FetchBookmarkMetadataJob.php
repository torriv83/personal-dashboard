<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Bookmark;
use App\Models\User;
use App\Notifications\MetadataFetched;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchBookmarkMetadataJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 600;

    private int $totalProcessed = 0;

    private int $updated = 0;

    private int $failed = 0;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $bookmarkId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $query = Bookmark::query();

        if ($this->bookmarkId) {
            $query->where('id', $this->bookmarkId);
        } else {
            // Fetch metadata for bookmarks missing title, description, or where title equals URL
            $query->where(function ($q) {
                $q->whereNull('title')
                    ->orWhere('title', '')
                    ->orWhereColumn('title', 'url')
                    ->orWhereNull('description')
                    ->orWhere('description', '');
            });
        }

        $bookmarks = $query->get();

        foreach ($bookmarks as $bookmark) {
            $this->fetchForBookmark($bookmark);
            $this->totalProcessed++;
        }

        // Send notification when processing all (not single)
        if ($this->bookmarkId === null && $this->totalProcessed > 0) {
            $this->sendNotification();
        }
    }

    /**
     * Fetch metadata for a single bookmark.
     */
    protected function fetchForBookmark(Bookmark $bookmark): void
    {
        try {
            $html = @file_get_contents($bookmark->url, false, stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'header' => 'User-Agent: Mozilla/5.0 (compatible; PersonalDashboard/1.0)',
                ],
            ]));

            if ($html === false) {
                $this->failed++;

                return;
            }

            $title = null;
            $description = null;

            // Extract title
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
                $title = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            }

            // Extract description
            if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/is', $html, $matches)) {
                $description = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\'][^>]*>/is', $html, $matches)) {
                $description = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            } elseif (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/is', $html, $matches)) {
                $description = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
            }

            $changes = [];

            if ($title !== null && $title !== '') {
                $changes['title'] = mb_substr($title, 0, 255);
            }

            if ($description !== null && $description !== '' && (empty($bookmark->description))) {
                $changes['description'] = $description;
            }

            if (! empty($changes)) {
                $bookmark->update($changes);
                $this->updated++;

                Log::info('Bookmark metadata updated', [
                    'id' => $bookmark->id,
                    'url' => $bookmark->url,
                    'changes' => array_keys($changes),
                ]);
            }
        } catch (\Exception $e) {
            $this->failed++;

            Log::warning('Failed to fetch bookmark metadata', [
                'id' => $bookmark->id,
                'url' => $bookmark->url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send push notification with results.
     */
    protected function sendNotification(): void
    {
        $user = User::first();

        if (! $user) {
            return;
        }

        $user->notify(new MetadataFetched(
            totalProcessed: $this->totalProcessed,
            updated: $this->updated,
            failed: $this->failed,
        ));
    }
}
