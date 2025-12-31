<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Bookmark;
use App\Models\BookmarkTag;
use App\Models\User;
use App\Notifications\DeadLinksChecked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckDeadBookmarksJob implements ShouldQueue
{
    use Queueable;

    private const DEAD_TAG_NAME = 'Død';

    private const DEAD_TAG_COLOR = 'red';

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    private int $totalChecked = 0;

    private int $newlyDead = 0;

    private int $revived = 0;

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
            $this->totalChecked++;
        }

        // Send push notification when checking all bookmarks (not single)
        if ($this->bookmarkId === null && $this->totalChecked > 0) {
            $this->sendNotification();
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

        $deadCount = Bookmark::where('is_dead', true)->count();

        $user->notify(new DeadLinksChecked(
            totalChecked: $this->totalChecked,
            deadFound: $deadCount,
            newlyDead: $this->newlyDead,
            revived: $this->revived
        ));
    }

    /**
     * Check if a bookmark URL is dead.
     */
    protected function checkBookmark(Bookmark $bookmark): void
    {
        $wasDead = $bookmark->is_dead;

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

                // Track statistics
                if ($isDead && ! $wasDead) {
                    $this->newlyDead++;
                } elseif (! $isDead && $wasDead) {
                    $this->revived++;
                }

                // Manage the "Død" tag
                $this->updateDeadTag($bookmark, $isDead);

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

                // Track as newly dead
                $this->newlyDead++;

                // Add the "Død" tag
                $this->updateDeadTag($bookmark, true);

                Log::warning('Bookmark marked as dead due to connection error', [
                    'id' => $bookmark->id,
                    'url' => $bookmark->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get or create the "Død" tag.
     */
    protected function getDeadTag(): BookmarkTag
    {
        return BookmarkTag::firstOrCreate(
            ['name' => self::DEAD_TAG_NAME],
            ['color' => self::DEAD_TAG_COLOR, 'sort_order' => 9999]
        );
    }

    /**
     * Attach or detach the "Død" tag based on bookmark status.
     */
    protected function updateDeadTag(Bookmark $bookmark, bool $isDead): void
    {
        $deadTag = $this->getDeadTag();

        if ($isDead) {
            // Attach tag if not already attached
            if (! $bookmark->tags()->where('tag_id', $deadTag->id)->exists()) {
                $bookmark->tags()->attach($deadTag->id);
            }
        } else {
            // Detach tag if attached
            $bookmark->tags()->detach($deadTag->id);
        }
    }
}
