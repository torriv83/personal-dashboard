<?php

declare(strict_types=1);

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
        Schema::create('hjelpemidler', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hjelpemiddel_kategori_id')->constrained('hjelpemiddel_kategorier')->cascadeOnDelete();
            $table->string('name');
            $table->string('url')->nullable();
            $table->json('custom_fields')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hjelpemidler');
    }
};
