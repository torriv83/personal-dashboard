<?php

namespace App\Jobs;

use App\Models\Bookmark;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckDeadBookmarksJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

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

        // If a specific bookmark ID is provided, only check that one
        if ($this->bookmarkId) {
            $query->where('id', $this->bookmarkId);
        }

        $bookmarks = $query->get();

        foreach ($bookmarks as $bookmark) {
            $this->checkBookmark($bookmark);
        }
    }

    /**
     * Check if a bookmark URL is dead.
     */
    protected function checkBookmark(Bookmark $bookmark): void
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; PersonalDashboard/1.0; DeadLinkChecker)',
                ])
                ->head($bookmark->url);

            // Consider dead if status is 4xx or 5xx (except 401, 403 which might be auth-protected)
            $isDead = $response->status() >= 400 && ! in_array($response->status(), [401, 403]);

            if ($bookmark->is_dead !== $isDead) {
                $bookmark->is_dead = $isDead;
                $bookmark->save();

                Log::info('Bookmark status updated', [
                    'id' => $bookmark->id,
                    'url' => $bookmark->url,
                    'is_dead' => $isDead,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            // Connection failed - mark as dead
            if (! $bookmark->is_dead) {
                $bookmark->is_dead = true;
                $bookmark->save();

                Log::warning('Bookmark marked as dead due to connection error', [
                    'id' => $bookmark->id,
                    'url' => $bookmark->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
