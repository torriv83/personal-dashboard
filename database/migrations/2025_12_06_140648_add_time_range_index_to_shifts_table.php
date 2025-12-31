<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds composite index for time range queries (overlap detection, calendar views).
     * This optimizes queries that filter by both starts_at and ends_at.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->index(['starts_at', 'ends_at'], 'shifts_time_range_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('shifts_time_range_idx');
        });
    }
};
