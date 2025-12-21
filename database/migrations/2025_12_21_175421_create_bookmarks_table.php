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
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('bookmark_folders')->nullOnDelete();
            $table->text('url');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('favicon_path')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_dead')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // MySQL supports index length prefix, SQLite does not
            if (DB::getDriverName() === 'mysql') {
                $table->rawIndex('url(255)', 'bookmarks_url_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
