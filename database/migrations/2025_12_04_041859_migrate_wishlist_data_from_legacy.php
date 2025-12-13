<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Map Norwegian status labels to English database values.
     *
     * @var array<string, string>
     */
    private array $statusMap = [
        'Venter' => 'waiting',
        'Begynt å spare' => 'saving',
        'Spart' => 'saved',
        'Kjøpt' => 'purchased',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->environment('production')) {
            return;
        }
        
        // Get all wishlists from the legacy database (exclude soft-deleted)
        $oldWishlists = DB::connection('legacy')
            ->table('wishlists')
            ->whereNull('deleted_at')
            ->orderBy('prioritet')
            ->get();

        // Get all wishlist items from the legacy database
        $oldWishlistItems = DB::connection('legacy')
            ->table('wishlist_items')
            ->get()
            ->groupBy('wishlist_id');

        $sortOrder = 0;

        foreach ($oldWishlists as $oldWishlist) {
            $childItems = $oldWishlistItems->get($oldWishlist->id, collect());

            if ($childItems->isNotEmpty()) {
                // This wishlist has child items, so it's a GROUP
                $groupId = DB::table('wishlist_groups')->insertGetId([
                    'name' => $oldWishlist->hva,
                    'sort_order' => $sortOrder++,
                    'created_at' => $oldWishlist->created_at,
                    'updated_at' => $oldWishlist->updated_at,
                ]);

                // Migrate the child items
                foreach ($childItems as $childItem) {
                    DB::table('wishlist_items')->insert([
                        'name' => $childItem->hva,
                        'url' => $childItem->url,
                        'price' => (int) $childItem->koster,
                        'quantity' => (int) $childItem->antall,
                        'status' => $this->mapStatus($childItem->status),
                        'sort_order' => 0, // Items in groups are sorted by created_at
                        'group_id' => $groupId,
                        'created_at' => $childItem->created_at,
                        'updated_at' => $childItem->updated_at,
                    ]);
                }
            } else {
                // This wishlist has no child items, so it's a STANDALONE ITEM
                DB::table('wishlist_items')->insert([
                    'name' => $oldWishlist->hva,
                    'url' => $oldWishlist->url,
                    'price' => (int) $oldWishlist->koster,
                    'quantity' => (int) $oldWishlist->antall,
                    'status' => $this->mapStatus($oldWishlist->status),
                    'sort_order' => $sortOrder++,
                    'group_id' => null,
                    'created_at' => $oldWishlist->created_at,
                    'updated_at' => $oldWishlist->updated_at,
                ]);
            }
        }
    }

    /**
     * Map Norwegian status to English database value.
     */
    private function mapStatus(?string $norwegianStatus): string
    {
        if ($norwegianStatus === null) {
            return 'waiting';
        }

        return $this->statusMap[$norwegianStatus] ?? 'waiting';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear all migrated data
        DB::table('wishlist_items')->truncate();
        DB::table('wishlist_groups')->truncate();
    }
};
