<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Shift;
use Illuminate\Console\Command;

class ArchivePastAbsences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shifts:archive-past-absences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive past absence (BORTE) entries automatically';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Count first, then bulk soft-delete
        $count = Shift::query()
            ->where('is_unavailable', true)
            ->where('starts_at', '<', now()->startOfDay())
            ->count();

        if ($count > 0) {
            Shift::query()
                ->where('is_unavailable', true)
                ->where('starts_at', '<', now()->startOfDay())
                ->delete(); // Bulk soft delete
        }

        $this->info("Archived {$count} past absence entries.");

        return self::SUCCESS;
    }
}
