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
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained('wishlist_groups')->nullOnDelete();
            $table->string('name');
            $table->string('url')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->enum('status', ['waiting', 'saving', 'saved', 'purchased'])->default('waiting');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
