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
     *
     * This migration converts is_archived=true records to use soft delete (deleted_at)
     * and then removes the redundant is_archived column.
     */
    public function up(): void
    {
        // Step 1: Convert is_archived=true records to soft deleted
        // Set deleted_at for any records that have is_archived=true but no deleted_at
        DB::table('shifts')
            ->where('is_archived', true)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        // Step 2: Drop the composite index that includes is_archived
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('shifts_worked_date_idx');
        });

        // Step 3: Drop the is_archived column
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('is_archived');
        });

        // Step 4: Create a new index without is_archived
        Schema::table('shifts', function (Blueprint $table) {
            $table->index(['is_unavailable', 'starts_at'], 'shifts_worked_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new index
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('shifts_worked_date_idx');
        });

        // Re-add the is_archived column
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('is_all_day');
        });

        // Convert soft-deleted records back to is_archived=true
        DB::table('shifts')
            ->whereNotNull('deleted_at')
            ->update(['is_archived' => true, 'deleted_at' => null]);

        // Re-create the original composite index
        Schema::table('shifts', function (Blueprint $table) {
            $table->index(['is_archived', 'is_unavailable', 'starts_at'], 'shifts_worked_date_idx');
        });
    }
};
