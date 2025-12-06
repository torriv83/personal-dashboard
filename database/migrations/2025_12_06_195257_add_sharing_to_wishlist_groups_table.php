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
        Schema::table('wishlist_groups', function (Blueprint $table) {
            $table->boolean('is_shared')->default(false)->after('sort_order');
            $table->string('share_token', 32)->nullable()->unique()->after('is_shared');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wishlist_groups', function (Blueprint $table) {
            $table->dropColumn(['is_shared', 'share_token']);
        });
    }
};
