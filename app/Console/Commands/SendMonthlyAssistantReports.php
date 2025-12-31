<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\MonthlyAssistantReport;
use App\Models\Assistant;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMonthlyAssistantReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assistants:send-monthly-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send månedlige arbeidstidsrapporter til assistenter som har aktivert dette';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year = now()->year;
        $month = now()->month;
        $hourlyRate = Setting::getBpaHourlyRate();

        $this->info("Sender månedlige rapporter for {$month}/{$year}...");

        $assistants = Assistant::query()
            ->where('send_monthly_report', true)
            ->whereNotNull('email')
            ->get();

        $sentCount = 0;

        foreach ($assistants as $assistant) {
            $shifts = $assistant->shifts()
                ->worked()
                ->forMonth($year, $month)
                ->orderBy('starts_at')
                ->get();

            if ($shifts->isEmpty()) {
                $this->info("Ingen vakter for {$assistant->name} i {$month}/{$year}, hopper over.");

                continue;
            }

            $totalMinutes = $shifts->sum(fn ($shift) => $shift->duration_minutes);
            $estimatedPay = ($totalMinutes / 60) * $hourlyRate;

            Mail::to($assistant->email)->queue(new MonthlyAssistantReport(
                assistant: $assistant,
                year: $year,
                month: $month,
                shifts: $shifts,
                totalMinutes: $totalMinutes,
                estimatedPay: $estimatedPay,
                hourlyRate: $hourlyRate
            ));

            $this->info("Sendt rapport til {$assistant->name} ({$assistant->email})");
            $sentCount++;
        }

        $this->info("Totalt sendt {$sentCount} rapporter.");

        return self::SUCCESS;
    }
}
