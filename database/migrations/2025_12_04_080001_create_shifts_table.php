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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('assistant_id')->nullable()->unsigned();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->integer('duration_minutes');
            $table->boolean('is_unavailable')->default(false);
            $table->boolean('is_all_day')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->text('note')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->foreign('assistant_id')->references('id')->on('assistants')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
