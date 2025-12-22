<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('is_dead');
            $table->unsignedInteger('pinned_order')->nullable()->after('is_pinned');

            $table->index(['is_pinned', 'pinned_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropIndex(['is_pinned', 'pinned_order']);
            $table->dropColumn(['is_pinned', 'pinned_order']);
        });
    }
};
