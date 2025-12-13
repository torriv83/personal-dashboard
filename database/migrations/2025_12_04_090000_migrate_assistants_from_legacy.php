<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Map Norwegian role names to English enum values.
     */
    private array $roleMap = [
        'Fast ansatt' => 'primary',
        'Tilkalling' => 'oncall',
        'Vikar' => 'substitute',
    ];

    /**
     * Default colors for assistant types.
     */
    private array $defaultColors = [
        'primary' => '#3b82f6',   // blue
        'substitute' => '#a855f7', // purple
        'oncall' => '#f97316',     // orange
    ];

    public function up(): void
    {
        if (app()->environment('production')) {
            return;
        }
        
        // Get all users with assistant roles from legacy database
        $legacyAssistants = DB::connection('legacy')
            ->table('users as u')
            ->leftJoin('model_has_roles as mhr', function ($join) {
                $join->on('mhr.model_id', '=', 'u.id')
                    ->where('mhr.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('roles as r', 'r.id', '=', 'mhr.role_id')
            ->whereIn('r.name', ['Fast ansatt', 'Tilkalling', 'Vikar'])
            ->select([
                'u.id',
                'u.name',
                'u.email',
                'u.phone',
                'u.assistentnummer',
                'u.ansatt_dato',
                'u.deleted_at',
                'u.created_at',
                'u.updated_at',
                'r.name as role_name',
            ])
            ->orderBy('u.id')
            ->get();

        $employeeNumber = 1;

        foreach ($legacyAssistants as $legacy) {
            $type = $this->roleMap[$legacy->role_name] ?? 'oncall';

            DB::table('assistants')->insert([
                'employee_number' => $employeeNumber++,
                'name' => $legacy->name,
                'email' => $legacy->email,
                'phone' => $legacy->phone ? (string) $legacy->phone : null,
                'type' => $type,
                'color' => $this->defaultColors[$type],
                'hired_at' => $legacy->ansatt_dato ?? $legacy->created_at,
                'deleted_at' => $legacy->deleted_at,
                'created_at' => $legacy->created_at,
                'updated_at' => $legacy->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('assistants')->truncate();
    }
};
