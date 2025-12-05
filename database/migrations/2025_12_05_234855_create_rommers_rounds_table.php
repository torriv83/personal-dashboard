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
        Schema::create('rommers_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('rommers_players')->cascadeOnDelete();
            $table->tinyInteger('round_number');
            $table->tinyInteger('level');
            $table->integer('score');
            $table->boolean('completed_level')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['player_id', 'round_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rommers_rounds');
    }
};
