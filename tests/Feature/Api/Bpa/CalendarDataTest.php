<?php

declare(strict_types=1);

use App\Models\Assistant;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);

    $this->mock(GoogleCalendarService::class, function ($mock) {
        $mock->shouldReceive('getAllEvents')->andReturn(collect());
    });

    Setting::set('bpa_hours_per_week', 37);
    Carbon::setTestNow(Carbon::create(2026, 2, 15, 10, 0, 0, 'Europe/Oslo'));
});

afterEach(function () {
    Carbon::setTestNow();
});

// Autentisering

it('krever innlogging for å se skift', function () {
    auth()->logout();
    getJson('/api/bpa/calendar/shifts?year=2026&month=2')
        ->assertUnauthorized();
});

it('krever innlogging for å se assistenter', function () {
    auth()->logout();
    getJson('/api/bpa/calendar/assistants')
        ->assertUnauthorized();
});

// Skift-endepunkt

it('returnerer skift for månedsvisning', function () {
    $assistant = Assistant::factory()->create();
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::create(2026, 2, 10, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 10, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    getJson('/api/bpa/calendar/shifts?year=2026&month=2&view=month')
        ->assertOk()
        ->assertJsonStructure([
            'shifts' => [
                '*' => [
                    'id',
                    'assistant_id',
                    'assistant_name',
                    'starts_at',
                    'ends_at',
                    'date',
                    'start_time',
                    'end_time',
                    'duration_minutes',
                    'formatted_duration',
                    'time_range',
                    'compact_time_range',
                    'is_unavailable',
                    'is_all_day',
                    'is_recurring',
                    'recurring_group_id',
                    'note',
                ],
            ],
            'shifts_by_date',
        ])
        ->assertJsonCount(1, 'shifts');
});

it('returnerer skift gruppert etter dato', function () {
    $assistant = Assistant::factory()->create();
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::create(2026, 2, 10, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 10, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    $response = getJson('/api/bpa/calendar/shifts?year=2026&month=2&view=month')
        ->assertOk();

    $byDate = $response->json('shifts_by_date');
    expect($byDate)->toHaveKey('2026-02-10');
    expect($byDate['2026-02-10'])->toHaveCount(1);
});

it('returnerer skift for ukesvisning', function () {
    $assistant = Assistant::factory()->create();
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::create(2026, 2, 11, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 11, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    getJson('/api/bpa/calendar/shifts?year=2026&month=2&day=11&view=week')
        ->assertOk()
        ->assertJsonCount(1, 'shifts');
});

it('returnerer skift for dagsvisning', function () {
    $assistant = Assistant::factory()->create();
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::create(2026, 2, 15, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 15, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);
    // Annen dag - skal ikke inkluderes
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::create(2026, 2, 16, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 16, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    getJson('/api/bpa/calendar/shifts?year=2026&month=2&day=15&view=day')
        ->assertOk()
        ->assertJsonCount(1, 'shifts');
});

it('validerer påkrevde parametre for skift-endepunkt', function () {
    getJson('/api/bpa/calendar/shifts')
        ->assertUnprocessable();
});

// Assistenter-endepunkt

it('returnerer alle aktive assistenter', function () {
    Assistant::factory()->count(3)->create();
    Assistant::factory()->create(['deleted_at' => now()]);

    getJson('/api/bpa/calendar/assistants')
        ->assertOk()
        ->assertJsonCount(3, 'assistants')
        ->assertJsonStructure([
            'assistants' => [
                '*' => [
                    'id',
                    'name',
                    'color',
                    'type',
                    'type_label',
                    'initials',
                    'short_name',
                    'employee_number',
                    'formatted_number',
                    'token',
                ],
            ],
        ]);
});

// Gjenstående timer-endepunkt

it('returnerer gjenstående timer for inneværende år', function () {
    $assistant = Assistant::factory()->create();
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::create(2026, 2, 1, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 1, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    getJson('/api/bpa/calendar/remaining-hours?year=2026')
        ->assertOk()
        ->assertJsonStructure([
            'hours_per_week',
            'total_minutes',
            'used_minutes',
            'remaining_minutes',
            'formatted_remaining',
            'percentage_used',
        ])
        ->assertJsonPath('used_minutes', 240);
});

// Tilgjengelige år

it('returnerer tilgjengelige år', function () {
    $assistant = Assistant::factory()->create();
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::create(2026, 1, 5, 8, 0),
        'ends_at' => Carbon::create(2026, 1, 5, 12, 0),
    ]);

    getJson('/api/bpa/calendar/available-years')
        ->assertOk()
        ->assertJsonStructure(['years'])
        ->assertJsonPath('years', fn ($years) => in_array(2026, $years));
});

// Kalenderrutenett

it('returnerer kalenderrutenett for en måned', function () {
    getJson('/api/bpa/calendar/days?year=2026&month=2')
        ->assertOk()
        ->assertJsonStructure([
            'weeks' => [
                '*' => [
                    'weekNumber',
                    'days' => [
                        '*' => [
                            'date',
                            'dayOfMonth',
                            'isCurrentMonth',
                            'isToday',
                            'isWeekend',
                            'weekNumber',
                            'dayOfWeek',
                        ],
                    ],
                ],
            ],
        ]);
});

it('returnerer korrekt antall uker for februar 2026', function () {
    $response = getJson('/api/bpa/calendar/days?year=2026&month=2')
        ->assertOk();

    $weeks = $response->json('weeks');
    expect(count($weeks))->toBeGreaterThanOrEqual(4);

    // Sjekk at første dag i første uke er en mandag
    $firstDay = $weeks[0]['days'][0];
    $carbon = Carbon::parse($firstDay['date']);
    expect($carbon->isMonday())->toBeTrue();
});

it('validerer påkrevde parametre for kalenderrutenett', function () {
    getJson('/api/bpa/calendar/days')
        ->assertUnprocessable();
});

// Eksterne hendelser

it('returnerer eksterne hendelser', function () {
    getJson('/api/bpa/calendar/external-events?year=2026&month=2&view=month')
        ->assertOk()
        ->assertJsonStructure([
            'events',
            'events_by_date',
        ]);
});
