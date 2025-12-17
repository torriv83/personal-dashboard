<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add token column to assistants table
        Schema::table('assistants', function (Blueprint $table) {
            $table->uuid('token')->nullable()->unique()->after('send_monthly_report');
        });

        // Generate tokens for existing assistants
        $assistants = DB::table('assistants')->get();
        foreach ($assistants as $assistant) {
            DB::table('assistants')
                ->where('id', $assistant->id)
                ->update(['token' => (string) Str::uuid()]);
        }

        // 2. Rename column in tasks table
        Schema::table('tasks', function (Blueprint $table) {
            // Drop the foreign key first
            $table->dropForeign(['todo_assistant_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            // Rename the column
            $table->renameColumn('todo_assistant_id', 'assistant_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            // Add the new foreign key pointing to assistants
            $table->foreign('assistant_id')
                ->references('id')
                ->on('assistants')
                ->nullOnDelete();
        });

        // 3. Drop todo_assistants table
        Schema::dropIfExists('todo_assistants');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Recreate todo_assistants table
        Schema::create('todo_assistants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->uuid('token')->unique();
            $table->timestamps();
        });

        // 2. Rename column back in tasks table
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['assistant_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->renameColumn('assistant_id', 'todo_assistant_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreign('todo_assistant_id')
                ->references('id')
                ->on('todo_assistants')
                ->nullOnDelete();
        });

        // 3. Remove token column from assistants
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
