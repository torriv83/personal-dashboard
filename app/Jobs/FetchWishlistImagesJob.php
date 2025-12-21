<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WishlistItem;
use App\Notifications\WishlistImagesFetched;
use App\Services\OpenGraphService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FetchWishlistImagesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public bool $refetch = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OpenGraphService $openGraphService): void
    {
        $mode = $this->refetch ? 'refetch all' : 'missing only';
        Log::info("FetchWishlistImagesJob: Starting image fetch process (mode: {$mode})");

        if ($this->refetch) {
            $this->clearExistingImages();
        }

        // Find items to process
        $items = WishlistItem::query()
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->whereNull('image_url')
            ->get();

        $totalItems = $items->count();
        $imagesFound = 0;
        $processed = 0;

        Log::info("FetchWishlistImagesJob: Found {$totalItems} items to process");

        foreach ($items as $item) {
            $processed++;

            try {
                Log::debug("FetchWishlistImagesJob: Processing item {$processed}/{$totalItems}", [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'url' => $item->url,
                ]);

                $imageUrl = $openGraphService->fetchImage($item->url);

                if ($imageUrl) {
                    $item->update(['image_url' => $imageUrl]);
                    $imagesFound++;

                    Log::debug("FetchWishlistImagesJob: Image found for item {$item->id}", [
                        'image_url' => $imageUrl,
                    ]);
                } else {
                    Log::debug("FetchWishlistImagesJob: No image found for item {$item->id}");
                }
            } catch (\Exception $e) {
                // Log the error but continue processing other items
                Log::warning("FetchWishlistImagesJob: Failed to process item {$item->id}", [
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'url' => $item->url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('FetchWishlistImagesJob: Completed', [
            'mode' => $mode,
            'total_items' => $totalItems,
            'processed' => $processed,
            'images_found' => $imagesFound,
            'images_not_found' => $totalItems - $imagesFound,
        ]);

        // Send push notification to user (only in production)
        if (app()->environment('production')) {
            $user = User::first();
            if ($user) {
                $user->notify(new WishlistImagesFetched($totalItems, $imagesFound));
            }
        }
    }

    /**
     * Clear all existing images and reset image_url for all items.
     */
    private function clearExistingImages(): void
    {
        Log::info('FetchWishlistImagesJob: Clearing existing images');

        // Get all items with local images
        $itemsWithLocalImages = WishlistItem::query()
            ->whereNotNull('image_url')
            ->where('image_url', 'like', '/storage/wishlist-images/%')
            ->get();

        // Delete the image files
        foreach ($itemsWithLocalImages as $item) {
            $path = str_replace('/storage/', '', $item->image_url);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                Log::debug('FetchWishlistImagesJob: Deleted image file', ['path' => $path]);
            }
        }

        // Reset image_url for all items
        WishlistItem::query()
            ->whereNotNull('image_url')
            ->update(['image_url' => null]);

        Log::info('FetchWishlistImagesJob: Cleared all existing images', [
            'local_files_deleted' => $itemsWithLocalImages->count(),
        ]);
    }
}
