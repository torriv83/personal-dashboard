<?php

declare(strict_types=1);

use App\Livewire\Bpa\Calendar;
use App\Models\Assistant;
use App\Models\Shift;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Mock GoogleCalendarService to avoid HTTP calls
    $this->mock(GoogleCalendarService::class, function ($mock) {
        $mock->shouldReceive('getAllEvents')->andReturn(collect());
    });

    // Set default BPA hours for quota validation
    \App\Models\Setting::set('bpa_hours_per_week', 37);

    // Freeze time for predictable tests
    Carbon::setTestNow(Carbon::create(2024, 6, 15, 10, 0, 0, 'Europe/Oslo'));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('renders the calendar page', function () {
    $this->get(route('bpa.calendar'))
        ->assertOk();
});

it('renders the calendar component', function () {
    Livewire::test(Calendar::class)
        ->assertStatus(200);
});

it('initializes with current date', function () {
    Livewire::test(Calendar::class)
        ->assertSet('year', 2024)
        ->assertSet('month', 6)
        ->assertSet('day', 15)
        ->assertSet('view', 'month');
});

// Navigation tests
it('can navigate to previous month', function () {
    Livewire::test(Calendar::class)
        ->assertSet('month', 6)
        ->call('previousMonth')
        ->assertSet('month', 5)
        ->assertSet('year', 2024);
});

it('can navigate to next month', function () {
    Livewire::test(Calendar::class)
        ->assertSet('month', 6)
        ->call('nextMonth')
        ->assertSet('month', 7)
        ->assertSet('year', 2024);
});

it('handles year rollover when going to previous month from January', function () {
    Carbon::setTestNow(Carbon::create(2024, 1, 15, 10, 0, 0, 'Europe/Oslo'));

    Livewire::test(Calendar::class)
        ->assertSet('month', 1)
        ->assertSet('year', 2024)
        ->call('previousMonth')
        ->assertSet('month', 12)
        ->assertSet('year', 2023);
});

it('handles year rollover when going to next month from December', function () {
    Carbon::setTestNow(Carbon::create(2024, 12, 15, 10, 0, 0, 'Europe/Oslo'));

    Livewire::test(Calendar::class)
        ->assertSet('month', 12)
        ->assertSet('year', 2024)
        ->call('nextMonth')
        ->assertSet('month', 1)
        ->assertSet('year', 2025);
});

it('can go to today', function () {
    $component = Livewire::test(Calendar::class)
        ->call('nextMonth')
        ->call('nextMonth')
        ->assertSet('month', 8);

    $component->call('goToToday')
        ->assertSet('year', 2024)
        ->assertSet('month', 6)
        ->assertSet('day', 15);
});

it('can navigate to previous day', function () {
    Livewire::test(Calendar::class)
        ->assertSet('day', 15)
        ->call('previousDay')
        ->assertSet('day', 14);
});

it('can navigate to next day', function () {
    Livewire::test(Calendar::class)
        ->assertSet('day', 15)
        ->call('nextDay')
        ->assertSet('day', 16);
});

it('can navigate to previous week', function () {
    Livewire::test(Calendar::class)
        ->assertSet('day', 15)
        ->call('previousWeek')
        ->assertSet('day', 8);
});

it('can navigate to next week', function () {
    Livewire::test(Calendar::class)
        ->assertSet('day', 15)
        ->call('nextWeek')
        ->assertSet('day', 22);
});

it('can go to specific day', function () {
    Livewire::test(Calendar::class)
        ->call('goToDay', '2024-07-20')
        ->assertSet('year', 2024)
        ->assertSet('month', 7)
        ->assertSet('day', 20)
        ->assertSet('view', 'day');
});

it('can change view', function () {
    Livewire::test(Calendar::class)
        ->assertSet('view', 'month')
        ->call('setView', 'week')
        ->assertSet('view', 'week')
        ->call('setView', 'day')
        ->assertSet('view', 'day');
});

// Helper property tests
it('returns correct Norwegian month name', function () {
    Livewire::test(Calendar::class)
        ->assertSet('year', 2024)
        ->assertSet('month', 6);

    $component = Livewire::test(Calendar::class);
    expect($component->get('currentMonthName'))->toBe('Juni');
});

it('returns assistants sorted by name', function () {
    Assistant::factory()->create(['name' => 'Charlie']);
    Assistant::factory()->create(['name' => 'Alice']);
    Assistant::factory()->create(['name' => 'Bob']);

    $component = Livewire::test(Calendar::class);
    $assistants = $component->get('assistants');

    expect($assistants[0]->name)->toBe('Alice');
    expect($assistants[1]->name)->toBe('Bob');
    expect($assistants[2]->name)->toBe('Charlie');
});

// Direct navigation tests
it('can go to specific month', function () {
    Livewire::test(Calendar::class)
        ->assertSet('month', 6)
        ->call('goToMonth', 3)
        ->assertSet('month', 3)
        ->assertSet('year', 2024); // Year should not change
});

it('can go to specific year', function () {
    Livewire::test(Calendar::class)
        ->assertSet('year', 2024)
        ->call('goToYear', 2023)
        ->assertSet('year', 2023)
        ->assertSet('month', 6); // Month should not change
});

it('returns available years from shift data', function () {
    // Create shifts in different years
    $assistant = Assistant::factory()->create();
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2022-05-15 08:00'),
        'ends_at' => Carbon::parse('2022-05-15 12:00'),
    ]);
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2023-08-20 08:00'),
        'ends_at' => Carbon::parse('2023-08-20 12:00'),
    ]);

    $component = Livewire::test(Calendar::class);
    $years = $component->get('availableYears');

    expect($years)->toContain(2022)
        ->and($years)->toContain(2023)
        ->and($years)->toContain(2024); // Current year always included
});
