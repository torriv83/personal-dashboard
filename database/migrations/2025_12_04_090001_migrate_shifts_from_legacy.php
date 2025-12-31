<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->environment('production')) {
            return;
        }

        // Build a mapping of legacy user emails to new assistant IDs
        $assistantMap = [];
        $assistants = DB::table('assistants')->get();
        foreach ($assistants as $assistant) {
            $assistantMap[$assistant->email] = $assistant->id;
        }

        // Get all timesheets from legacy database
        $legacyTimesheets = DB::connection('legacy')
            ->table('timesheets as t')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->select([
                't.id',
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
            ->orderBy('t.id')
            ->get();

        foreach ($legacyTimesheets as $legacy) {
            // Find the assistant ID by email
            $assistantId = $assistantMap[$legacy->user_email] ?? null;

            DB::table('shifts')->insert([
                'assistant_id' => $assistantId,
                'starts_at' => $legacy->fra_dato,
                'ends_at' => $legacy->til_dato,
                'duration_minutes' => $legacy->totalt,
                'is_unavailable' => (bool) $legacy->unavailable,
                'is_all_day' => (bool) $legacy->allDay,
                'is_archived' => false,
                'note' => $legacy->description,
                'deleted_at' => $legacy->deleted_at,
                'created_at' => $legacy->created_at,
                'updated_at' => $legacy->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('shifts')->truncate();
    }
};
