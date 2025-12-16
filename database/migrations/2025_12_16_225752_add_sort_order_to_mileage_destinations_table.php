<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mileage_destinations', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('distance_km');
        });

        // Set initial sort order based on name
        DB::table('mileage_destinations')
            ->orderBy('name')
            ->get()
            ->each(function ($destination, $index) {
                DB::table('mileage_destinations')
                    ->where('id', $destination->id)
                    ->update(['sort_order' => $index]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mileage_destinations', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
