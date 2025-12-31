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
        Schema::create('rommers_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('rommers_games')->cascadeOnDelete();
            $table->string('name');
            $table->tinyInteger('current_level')->default(1);
            $table->integer('total_score')->default(0);
            $table->tinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['game_id', 'sort_order']);
        });

        // Add foreign key for winner_id after players table exists
        Schema::table('rommers_games', function (Blueprint $table) {
            $table->foreign('winner_id')->references('id')->on('rommers_players')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rommers_games', function (Blueprint $table) {
            $table->dropForeign(['winner_id']);
        });
        Schema::dropIfExists('rommers_players');
    }
};
