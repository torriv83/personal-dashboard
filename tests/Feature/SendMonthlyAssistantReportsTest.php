<?php

declare(strict_types=1);

use App\Mail\MonthlyAssistantReport;
use App\Models\Assistant;
use App\Models\Setting;
use App\Models\Shift;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('sends monthly report to assistant with toggle enabled', function () {
    $assistant = Assistant::factory()->withMonthlyReport()->create();

    // Create shifts for current month
    Shift::factory()->count(3)->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->startOfMonth()->addDays(5)->setTime(8, 0),
        'ends_at' => now()->startOfMonth()->addDays(5)->setTime(12, 0),
        'duration_minutes' => 240,
        'is_unavailable' => false,
    ]);

    Setting::setBpaHourlyRate(225.40);

    $this->artisan('assistants:send-monthly-reports')->assertSuccessful();

    Mail::assertQueued(MonthlyAssistantReport::class, function ($mail) use ($assistant) {
        return $mail->assistant->id === $assistant->id;
    });
});

it('does not send report to assistant with toggle disabled', function () {
    $assistant = Assistant::factory()->create(['send_monthly_report' => false]);

    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->startOfMonth()->addDays(5)->setTime(8, 0),
        'ends_at' => now()->startOfMonth()->addDays(5)->setTime(12, 0),
        'is_unavailable' => false,
    ]);

    $this->artisan('assistants:send-monthly-reports')->assertSuccessful();

    Mail::assertNotQueued(MonthlyAssistantReport::class);
});

it('does not send report to assistant without email', function () {
    $assistant = Assistant::factory()->withMonthlyReport()->create(['email' => null]);

    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->startOfMonth()->addDays(5)->setTime(8, 0),
        'ends_at' => now()->startOfMonth()->addDays(5)->setTime(12, 0),
        'is_unavailable' => false,
    ]);

    $this->artisan('assistants:send-monthly-reports')->assertSuccessful();

    Mail::assertNotQueued(MonthlyAssistantReport::class);
});

it('does not send report if no shifts that month', function () {
    $assistant = Assistant::factory()->withMonthlyReport()->create();
    // No shifts created

    $this->artisan('assistants:send-monthly-reports')->assertSuccessful();

    Mail::assertNotQueued(MonthlyAssistantReport::class);
});

it('calculates estimated pay correctly', function () {
    $assistant = Assistant::factory()->withMonthlyReport()->create();

    // 4 hours = 240 minutes
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->startOfMonth()->addDays(5)->setTime(8, 0),
        'ends_at' => now()->startOfMonth()->addDays(5)->setTime(12, 0),
        'duration_minutes' => 240,
        'is_unavailable' => false,
    ]);

    Setting::setBpaHourlyRate(225.40);

    $this->artisan('assistants:send-monthly-reports')->assertSuccessful();

    Mail::assertQueued(MonthlyAssistantReport::class, function ($mail) {
        // 4 hours * 225.40 = 901.60
        return abs($mail->estimatedPay - 901.60) < 0.01;
    });
});

it('skips unavailable shifts in calculation', function () {
    $assistant = Assistant::factory()->withMonthlyReport()->create();

    // Regular shift - should be included
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->startOfMonth()->addDays(5)->setTime(8, 0),
        'ends_at' => now()->startOfMonth()->addDays(5)->setTime(12, 0),
        'duration_minutes' => 240,
        'is_unavailable' => false,
    ]);

    // Unavailable shift - should be excluded
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->startOfMonth()->addDays(6)->setTime(8, 0),
        'ends_at' => now()->startOfMonth()->addDays(6)->setTime(16, 0),
        'duration_minutes' => 480,
        'is_unavailable' => true,
    ]);

    Setting::setBpaHourlyRate(100.00);

    $this->artisan('assistants:send-monthly-reports')->assertSuccessful();

    Mail::assertQueued(MonthlyAssistantReport::class, function ($mail) {
        // Only 4 hours * 100 = 400 (excluding unavailable)
        return abs($mail->estimatedPay - 400.00) < 0.01 && $mail->shifts->count() === 1;
    });
});
