<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migrate legacy timesheets from former employees who are not in the assistants table.
     * These will be stored with assistant_id = NULL and displayed as "Tidligere ansatt".
     */
    public function up(): void
    {
        if (app()->environment('production')) {
            return;
        }

        // Get all emails from current assistants
        $assistantEmails = DB::table('assistants')->pluck('email')->toArray();

        // Get timesheets from legacy database where the user is NOT in assistants table
        // and is NOT Tor (the admin, identified by email containing 'torriv')
        $legacyTimesheets = DB::connection('legacy')
            ->table('timesheets as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->select([
                't.fra_dato',
                't.til_dato',
                't.description',
                't.totalt',
                't.unavailable',
                't.allDay',
                't.deleted_at',
                't.created_at',
                't.updated_at',
                'u.email as user_email',
            ])
            ->whereNotNull('u.email')
            ->where('u.email', 'not like', '%torriv%')
            ->whereNotIn('u.email', $assistantEmails)
            ->orderBy('t.id')
            ->get();

        foreach ($legacyTimesheets as $legacy) {
            DB::table('shifts')->insert([
                'assistant_id' => null,
                'starts_at' => $legacy->fra_dato,
                'ends_at' => $legacy->til_dato,
                'duration_minutes' => $legacy->totalt,
                'is_unavailable' => (bool) $legacy->unavailable,
                'is_all_day' => (bool) $legacy->allDay,
                'note' => $legacy->description,
                'deleted_at' => null, // Don't copy deleted_at - we want these visible
                'created_at' => $legacy->created_at,
                'updated_at' => $legacy->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migration by removing shifts with null assistant_id.
     */
    public function down(): void
    {
        DB::table('shifts')->whereNull('assistant_id')->delete();
    }
};
