<?php

declare(strict_types=1);

use App\Livewire\Bpa\Dashboard;
use App\Models\Assistant;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Set default BPA hours
    Setting::set('bpa_hours_per_week', 37);
});

it('renders the bpa dashboard', function () {
    $this->get(route('bpa.dashboard'))
        ->assertOk()
        ->assertSee('BPA');
});

it('shows correct assistant count', function () {
    Assistant::factory()->count(3)->create();

    $component = Livewire::test(Dashboard::class);
    $stats = $component->get('stats');

    expect($stats['stat_assistants']['label'])->toBe('Antall Assistenter');
    expect($stats['stat_assistants']['value'])->toBe('3');
});

it('shows upcoming shifts', function () {
    $assistant = Assistant::factory()->create(['name' => 'Test Assistent']);

    Shift::factory()->upcoming()->create([
        'assistant_id' => $assistant->id,
    ]);

    $component = Livewire::test(Dashboard::class);
    $upcomingShifts = $component->get('upcomingShifts');

    expect($upcomingShifts)->toHaveCount(1);
    expect($upcomingShifts[0]['name'])->toBe('Test'); // Only first name is shown
});

it('shows employees list', function () {
    $assistant = Assistant::factory()->create(['name' => 'Worker One']);

    $component = Livewire::test(Dashboard::class);
    $employees = $component->get('employees');

    expect($employees)->toHaveCount(1);
    expect($employees[0]['name'])->toBe('Worker One');
});

it('shows unavailable employees', function () {
    $assistant = Assistant::factory()->create(['name' => 'Unavailable Worker']);

    Shift::factory()->unavailable()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(3),
    ]);

    $component = Livewire::test(Dashboard::class);
    $unavailable = $component->get('unavailableEmployees');

    expect($unavailable)->toHaveCount(1);
    expect($unavailable[0]['name'])->toBe('Unavailable Worker');
});

it('can paginate upcoming shifts', function () {
    $assistant = Assistant::factory()->create();

    // Create 15 upcoming shifts
    Shift::factory()->count(15)->upcoming()->create([
        'assistant_id' => $assistant->id,
    ]);

    $component = Livewire::test(Dashboard::class);

    // Default is 10 per page
    expect($component->get('upcomingShifts'))->toHaveCount(10);

    // Go to next page
    $component->call('nextPage', 'shifts');
    expect($component->get('upcomingShifts'))->toHaveCount(5);

    // Go back
    $component->call('prevPage', 'shifts');
    expect($component->get('upcomingShifts'))->toHaveCount(10);
});
