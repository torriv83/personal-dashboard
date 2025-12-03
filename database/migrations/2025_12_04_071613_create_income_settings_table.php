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
        Schema::create('income_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('monthly_gross', 10, 2)->default(0); // Før skatt (mnd)
            $table->decimal('monthly_net', 10, 2)->default(0);   // Etter skatt (mnd)
            $table->string('tax_table')->nullable();              // Skattetabell
            $table->decimal('base_support', 10, 2)->default(0);  // Grunnstønad
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_settings');
    }
};
