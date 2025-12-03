<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migrate prescription data from legacy database.
     */
    public function up(): void
    {
        $legacyPrescriptions = DB::connection('legacy')
            ->table('resepters')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get();

        foreach ($legacyPrescriptions as $legacy) {
            DB::table('prescriptions')->insert([
                'name' => $legacy->name,
                'valid_to' => $legacy->validTo,
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
        DB::table('prescriptions')->truncate();
    }
};
