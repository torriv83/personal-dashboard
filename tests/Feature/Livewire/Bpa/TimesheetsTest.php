<?php

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
        'is_archived' => false,
    ]);

    Livewire::test(Timesheets::class)
        ->call('toggleField', $shift->id, 'away');

    $shift->refresh();
    expect($shift->is_unavailable)->toBeTrue();

    Livewire::test(Timesheets::class)
        ->call('toggleField', $shift->id, 'fullDay');

    $shift->refresh();
    expect($shift->is_all_day)->toBeTrue();

    Livewire::test(Timesheets::class)
        ->call('toggleField', $shift->id, 'archived');

    $shift->refresh();
    expect($shift->is_archived)->toBeTrue();
});
