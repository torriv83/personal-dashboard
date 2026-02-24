<?php

declare(strict_types=1);

use App\Models\Assistant;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);

    $this->mock(GoogleCalendarService::class, function ($mock) {
        $mock->shouldReceive('getAllEvents')->andReturn(collect());
    });

    Setting::set('bpa_hours_per_week', 37);
    Carbon::setTestNow(Carbon::create(2026, 2, 15, 10, 0, 0, 'Europe/Oslo'));

    $this->assistant = Assistant::factory()->create(['name' => 'Test Assistent']);
});

afterEach(function () {
    Carbon::setTestNow();
});

// Opprett enkelt skift

it('oppretter et enkelt skift', function () {
    postJson('/api/bpa/shifts', [
        'assistant_id' => $this->assistant->id,
        'from_date' => '2026-02-20',
        'from_time' => '08:00',
        'to_date' => '2026-02-20',
        'to_time' => '12:00',
        'is_unavailable' => false,
        'is_all_day' => false,
    ])
        ->assertCreated()
        ->assertJsonStructure([
            'shift' => [
                'id',
                'assistant_id',
                'starts_at',
                'ends_at',
                'duration_minutes',
                'is_unavailable',
            ],
            'message',
        ])
        ->assertJsonPath('shift.duration_minutes', 240);

    expect(Shift::where('assistant_id', $this->assistant->id)->count())->toBe(1);
});

it('validerer påkrevde felter ved opprettelse', function () {
    postJson('/api/bpa/shifts', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['assistant_id', 'from_date', 'to_date']);
});

it('returnerer 422 ved utilgjengelighetskonflikt', function () {
    // Opprett en utilgjengelighetsoppføring
    Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 20, 7, 0),
        'ends_at' => Carbon::create(2026, 2, 20, 13, 0),
        'is_unavailable' => true,
        'is_all_day' => false,
    ]);

    postJson('/api/bpa/shifts', [
        'assistant_id' => $this->assistant->id,
        'from_date' => '2026-02-20',
        'from_time' => '08:00',
        'to_date' => '2026-02-20',
        'to_time' => '12:00',
        'is_unavailable' => false,
        'is_all_day' => false,
    ])
        ->assertStatus(422)
        ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'borte'));
});

it('returnerer 422 når kvoten er brukt opp', function () {
    // Sett kvote til 1 time per uke = 52 timer totalt (3120 minutter)
    Setting::set('bpa_hours_per_week', 1);

    // Fyll opp nesten all kvoten (3100 minutter av 3120)
    Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 1, 1, 8, 0),
        'ends_at' => Carbon::create(2026, 1, 1, 8, 0)->addMinutes(3100),
        'is_unavailable' => false,
        'is_all_day' => false,
        'duration_minutes' => 3100,
    ]);

    // Prøv å lage et skift på 60 minutter (overskrider resterende 20 min)
    postJson('/api/bpa/shifts', [
        'assistant_id' => $this->assistant->id,
        'from_date' => '2026-02-20',
        'from_time' => '08:00',
        'to_date' => '2026-02-20',
        'to_time' => '09:00',
        'is_unavailable' => false,
        'is_all_day' => false,
    ])
        ->assertStatus(422);
});

// Gjentagende skift

it('oppretter gjentagende utilgjengelighetsoppføringer', function () {
    postJson('/api/bpa/shifts', [
        'assistant_id' => $this->assistant->id,
        'from_date' => '2026-02-16',
        'from_time' => '00:00',
        'to_date' => '2026-02-16',
        'to_time' => '23:59',
        'is_unavailable' => true,
        'is_all_day' => true,
        'is_recurring' => true,
        'recurring_interval' => 'weekly',
        'recurring_end_type' => 'count',
        'recurring_count' => 3,
    ])
        ->assertCreated()
        ->assertJsonStructure(['shifts', 'message'])
        ->assertJsonCount(3, 'shifts');

    expect(Shift::count())->toBe(3);

    // Alle skal ha samme recurring_group_id
    $groupIds = Shift::pluck('recurring_group_id')->unique();
    expect($groupIds)->toHaveCount(1);
    expect($groupIds->first())->not->toBeNull();
});

it('oppretter gjentagende skift med sluttdato', function () {
    postJson('/api/bpa/shifts', [
        'assistant_id' => $this->assistant->id,
        'from_date' => '2026-02-16',
        'from_time' => '00:00',
        'to_date' => '2026-02-16',
        'to_time' => '23:59',
        'is_unavailable' => true,
        'is_all_day' => true,
        'is_recurring' => true,
        'recurring_interval' => 'weekly',
        'recurring_end_type' => 'date',
        'recurring_end_date' => '2026-03-15',
    ])
        ->assertCreated();

    // 16 feb, 23 feb, 2 mar, 9 mar = 4 skift
    expect(Shift::count())->toBe(4);
});

// Oppdater skift

it('oppdaterer et enkelt skift', function () {
    $shift = Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 20, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 20, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    putJson("/api/bpa/shifts/{$shift->id}", [
        'assistant_id' => $this->assistant->id,
        'from_date' => '2026-02-20',
        'from_time' => '09:00',
        'to_date' => '2026-02-20',
        'to_time' => '13:00',
        'is_unavailable' => false,
        'is_all_day' => false,
    ])
        ->assertOk()
        ->assertJsonPath('shift.start_time', '09:00')
        ->assertJsonPath('shift.end_time', '13:00')
        ->assertJsonPath('message', 'Vakt oppdatert');
});

it('oppdaterer gjentagende skift med scope=all', function () {
    $groupId = Str::uuid()->toString();
    $shift1 = Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 16, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 16, 12, 0),
        'is_unavailable' => true,
        'is_all_day' => true,
        'recurring_group_id' => $groupId,
    ]);
    Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 23, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 23, 12, 0),
        'is_unavailable' => true,
        'is_all_day' => true,
        'recurring_group_id' => $groupId,
    ]);

    putJson("/api/bpa/shifts/{$shift1->id}", [
        'assistant_id' => $this->assistant->id,
        'from_date' => '2026-02-16',
        'from_time' => '00:00',
        'to_date' => '2026-02-16',
        'to_time' => '23:59',
        'is_unavailable' => true,
        'is_all_day' => true,
        'scope' => 'all',
        'note' => 'Oppdatert note',
    ])
        ->assertOk()
        ->assertJsonPath('message', '2 oppføringer ble oppdatert');

    // Begge skift skal ha den nye noten
    expect(Shift::where('recurring_group_id', $groupId)->where('note', 'Oppdatert note')->count())->toBe(2);
});

// Slett skift

it('sletter et enkelt skift permanent', function () {
    $shift = Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 20, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 20, 12, 0),
    ]);

    deleteJson("/api/bpa/shifts/{$shift->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Vakt slettet');

    expect(Shift::withTrashed()->find($shift->id))->toBeNull();
});

it('arkiverer (soft-sletter) et skift', function () {
    $shift = Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 20, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 20, 12, 0),
    ]);

    deleteJson("/api/bpa/shifts/{$shift->id}?type=archive")
        ->assertOk()
        ->assertJsonPath('message', 'Vakt arkivert');

    expect(Shift::find($shift->id))->toBeNull();
    expect(Shift::withTrashed()->find($shift->id))->not->toBeNull();
});

it('sletter fremtidige gjentagende skift', function () {
    $groupId = Str::uuid()->toString();
    $shift1 = Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 16, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 16, 12, 0),
        'recurring_group_id' => $groupId,
    ]);
    Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 23, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 23, 12, 0),
        'recurring_group_id' => $groupId,
    ]);
    Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 3, 2, 8, 0),
        'ends_at' => Carbon::create(2026, 3, 2, 12, 0),
        'recurring_group_id' => $groupId,
    ]);

    // Slett fra shift2 (23. feb) og fremover
    $shift2 = Shift::where('recurring_group_id', $groupId)
        ->orderBy('starts_at')
        ->skip(1)
        ->first();

    deleteJson("/api/bpa/shifts/{$shift2->id}?scope=future")
        ->assertOk()
        ->assertJsonPath('message', '2 oppføringer ble slettet');

    // Bare shift1 skal gjenværende
    expect(Shift::withTrashed()->where('recurring_group_id', $groupId)->count())->toBe(1);
});

// Flytt skift

it('flytter et skift til ny dato', function () {
    $shift = Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 20, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 20, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    postJson("/api/bpa/shifts/{$shift->id}/move", [
        'new_date' => '2026-02-25',
        'new_time' => '09:00',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Vakt flyttet')
        ->assertJsonPath('shift.date', '2026-02-25');

    expect($shift->fresh()->starts_at->format('Y-m-d'))->toBe('2026-02-25');
});

it('returnerer 422 når flytt skaper konflikt', function () {
    Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 25, 6, 0),
        'ends_at' => Carbon::create(2026, 2, 25, 18, 0),
        'is_unavailable' => true,
        'is_all_day' => false,
    ]);

    $shift = Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 20, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 20, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    postJson("/api/bpa/shifts/{$shift->id}/move", [
        'new_date' => '2026-02-25',
        'new_time' => '09:00',
    ])
        ->assertStatus(422);
});

// Endre varighet

it('endrer varigheten på et skift', function () {
    $shift = Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 20, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 20, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    postJson("/api/bpa/shifts/{$shift->id}/resize", [
        'duration_minutes' => 300,
    ])
        ->assertOk()
        ->assertJsonPath('message', 'Varighet endret')
        ->assertJsonPath('shift.duration_minutes', 300);

    expect($shift->fresh()->ends_at->format('H:i'))->toBe('13:00');
});

it('validerer minimum varighet på 15 minutter', function () {
    $shift = Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 20, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 20, 12, 0),
    ]);

    postJson("/api/bpa/shifts/{$shift->id}/resize", [
        'duration_minutes' => 5,
    ])
        ->assertUnprocessable();
});

// Dupliser skift

it('dupliserer et skift til same dato', function () {
    $shift = Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 20, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 20, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    postJson("/api/bpa/shifts/{$shift->id}/duplicate")
        ->assertCreated()
        ->assertJsonPath('message', 'Vakt duplisert')
        ->assertJsonPath('shift.date', '2026-02-20');

    expect(Shift::count())->toBe(2);
});

it('dupliserer et skift til ny dato', function () {
    $shift = Shift::factory()->create([
        'assistant_id' => $this->assistant->id,
        'starts_at' => Carbon::create(2026, 2, 20, 8, 0),
        'ends_at' => Carbon::create(2026, 2, 20, 12, 0),
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    postJson("/api/bpa/shifts/{$shift->id}/duplicate", [
        'target_date' => '2026-03-01',
    ])
        ->assertCreated()
        ->assertJsonPath('shift.date', '2026-03-01');

    expect(Shift::count())->toBe(2);
    expect(Shift::orderByDesc('id')->first()->starts_at->format('Y-m-d'))->toBe('2026-03-01');
});

// Hurtigopprettelse

it('hurtigoppretter et skift', function () {
    postJson('/api/bpa/shifts/quick-create', [
        'assistant_id' => $this->assistant->id,
        'date' => '2026-02-20',
        'time' => '10:00',
    ])
        ->assertCreated()
        ->assertJsonPath('message', 'Vakt opprettet')
        ->assertJsonPath('shift.start_time', '10:00');

    $shift = Shift::first();
    expect($shift)->not->toBeNull();
    // Standard 3 timers varighet
    expect($shift->ends_at->format('H:i'))->toBe('13:00');
});

it('hurtigoppretter et skift med tilpasset sluttid', function () {
    postJson('/api/bpa/shifts/quick-create', [
        'assistant_id' => $this->assistant->id,
        'date' => '2026-02-20',
        'time' => '10:00',
        'end_time' => '14:30',
    ])
        ->assertCreated()
        ->assertJsonPath('shift.end_time', '14:30');
});

it('krever innlogging for å opprette skift', function () {
    auth()->logout();
    postJson('/api/bpa/shifts', [
        'assistant_id' => $this->assistant->id,
        'from_date' => '2026-02-20',
        'from_time' => '08:00',
        'to_date' => '2026-02-20',
        'to_time' => '12:00',
    ])
        ->assertUnauthorized();
});
