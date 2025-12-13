<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migrate category and equipment data from legacy database.
     */
    public function up(): void
    {
        if (app()->environment('production')) {
            return;
        }
        
        $categoryIdMap = [];

        // 1. Migrate categories
        $legacyCategories = DB::connection('legacy')
            ->table('kategori')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        foreach ($legacyCategories as $legacy) {
            $newId = DB::table('categories')->insertGetId([
                'name' => $legacy->kategori,
                'created_at' => $legacy->created_at,
                'updated_at' => $legacy->updated_at,
            ]);

            $categoryIdMap[$legacy->id] = $newId;
        }

        // 2. Migrate equipment
        $legacyEquipment = DB::connection('legacy')
            ->table('utstyr')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        foreach ($legacyEquipment as $legacy) {
            // Skip if category wasn't migrated
            if (! isset($categoryIdMap[$legacy->kategori_id])) {
                continue;
            }

            DB::table('equipment')->insert([
                'type' => $legacy->hva,
                'name' => $legacy->navn,
                'article_number' => $legacy->artikkelnummer,
                'link' => $legacy->link,
                'category_id' => $categoryIdMap[$legacy->kategori_id],
                'quantity' => $legacy->antall,
                'created_at' => $legacy->created_at,
                'updated_at' => $legacy->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        DB::table('equipment')->truncate();
        DB::table('categories')->truncate();
    }
};
