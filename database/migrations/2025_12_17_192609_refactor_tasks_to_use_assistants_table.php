<?php

declare(strict_types=1);

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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
