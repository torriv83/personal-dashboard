<?php

declare(strict_types=1);

use App\Livewire\Bpa\Timesheets;
use App\Models\Assistant;
use App\Models\Shift;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    // Truncate tables to ensure clean state
    Shift::query()->forceDelete();
    Assistant::query()->forceDelete();

    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the timesheets page', function () {
    $this->get(route('bpa.timesheets'))
        ->assertOk()
        ->assertSee('Timelister');
});

it('can create a new shift', function () {
    $assistant = Assistant::factory()->create();

    Livewire::test(Timesheets::class)
        ->call('openCreateModal')
        ->set('assistant_id', $assistant->id)
        ->set('date', '2024-06-15')
        ->set('start_time', '09:00')
        ->set('end_time', '13:00')
        ->call('save')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('shifts', [
        'assistant_id' => $assistant->id,
    ]);
});

it('can update an existing shift', function () {
    $assistant = Assistant::factory()->create();
    $newAssistant = Assistant::factory()->create();

    $shift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
    ]);

    Livewire::test(Timesheets::class)
        ->call('openEditModal', $shift->id)
        ->set('assistant_id', $newAssistant->id)
        ->call('save')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('shifts', [
        'id' => $shift->id,
        'assistant_id' => $newAssistant->id,
    ]);
});

it('can delete a shift', function () {
    $shift = Shift::factory()->create();

    Livewire::test(Timesheets::class)
        ->call('delete', $shift->id)
        ->assertDispatched('toast');

    $this->assertSoftDeleted('shifts', ['id' => $shift->id]);
});

it('can toggle shift fields', function () {
    $shift = Shift::factory()->create([
        'is_unavailable' => false,
        'is_all_day' => false,
    ]);

    Livewire::test(Timesheets::class)
        ->call('toggleField', $shift->id, 'away');

    $shift->refresh();
    expect($shift->is_unavailable)->toBeTrue();

    Livewire::test(Timesheets::class)
        ->call('toggleField', $shift->id, 'fullDay');

    $shift->refresh();
    expect($shift->is_all_day)->toBeTrue();
});

it('returns years from shifts in descending order', function () {
    Shift::factory()->forYear(2022)->create();
    Shift::factory()->forYear(2023)->create();
    Shift::factory()->forYear(2021)->create();

    $component = Livewire::test(Timesheets::class);
    $years = $component->availableYears;

    expect($years)->toBeArray()
        ->and($years)->toContain(2023, 2022, 2021)
        ->and($years[0])->toBeGreaterThanOrEqual($years[1])
        ->and($years[1])->toBeGreaterThanOrEqual($years[2]);
});

it('includes current year as fallback when no shifts exist', function () {
    $currentYear = now()->year;

    $component = Livewire::test(Timesheets::class);
    $years = $component->availableYears;

    expect($years)->toBeArray()
        ->and($years)->toContain($currentYear)
        ->and($years)->toHaveCount(1);
});

it('extracts multiple years from different shifts correctly', function () {
    $currentYear = now()->year;

    Shift::factory()->forYear(2020)->count(3)->create();
    Shift::factory()->forYear(2021)->count(2)->create();
    Shift::factory()->forYear(2023)->create();

    $component = Livewire::test(Timesheets::class);
    $years = $component->availableYears;

    expect($years)->toBeArray()
        ->and($years)->toContain(2023, 2021, 2020);

    // Should include current year if not in the shift years
    if ($currentYear !== 2023 && $currentYear !== 2021 && $currentYear !== 2020) {
        expect($years)->toContain($currentYear);
    }
});

it('does not duplicate years when multiple shifts exist in same year', function () {
    Shift::factory()->forYear(2022)->count(5)->create();

    $component = Livewire::test(Timesheets::class);
    $years = $component->availableYears;

    $yearCount2022 = count(array_filter($years, fn ($year) => $year === 2022));

    expect($yearCount2022)->toBe(1);
});

it('excludes years from trashed shifts', function () {
    Shift::factory()->forYear(2020)->create();
    Shift::factory()->forYear(2021)->archived()->create();

    $component = Livewire::test(Timesheets::class);
    $years = $component->availableYears;

    expect($years)->toContain(2020)
        ->and($years)->not->toContain(2021);
});
