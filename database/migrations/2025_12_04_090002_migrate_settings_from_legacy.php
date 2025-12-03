<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get settings from legacy database
        $legacySettings = DB::connection('legacy')
            ->table('settings')
            ->first();

        if ($legacySettings) {
            DB::table('settings')->insert([
                'key' => 'bpa_hours_per_week',
                'value' => $legacySettings->bpa_hours_per_week,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'bpa_hours_per_week')->delete();
    }
};
