<?php

declare(strict_types=1);

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
        Schema::table('weight_entries', function (Blueprint $table) {
            // Drop the unique constraint and index on date
            $table->dropUnique(['date']);
            $table->dropIndex(['date']);
        });

        Schema::table('weight_entries', function (Blueprint $table) {
            // Rename date to recorded_at and change to datetime
            $table->renameColumn('date', 'recorded_at');
        });

        Schema::table('weight_entries', function (Blueprint $table) {
            // Change column type to datetime
            $table->dateTime('recorded_at')->change();
        });

        // Update existing records to have a time component (noon)
        DB::table('weight_entries')->update([
            'recorded_at' => DB::raw("CONCAT(DATE(recorded_at), ' 12:00:00')"),
        ]);

        Schema::table('weight_entries', function (Blueprint $table) {
            // Add new index
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weight_entries', function (Blueprint $table) {
            $table->dropIndex(['recorded_at']);
        });

        Schema::table('weight_entries', function (Blueprint $table) {
            $table->renameColumn('recorded_at', 'date');
        });

        Schema::table('weight_entries', function (Blueprint $table) {
            $table->date('date')->change();
            $table->unique(['date']);
            $table->index('date');
        });
    }
};
