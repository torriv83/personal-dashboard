<?php

declare(strict_types=1);

use App\Livewire\Bpa\Assistants;
use App\Models\Assistant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    // Truncate tables to ensure clean state
    Assistant::query()->forceDelete();

    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the assistants page', function () {
    $this->get(route('bpa.assistants'))
        ->assertOk()
        ->assertSee('Assistenter');
});

it('lists all active assistants', function () {
    Assistant::factory()->create(['name' => 'Test Assistent']);
    Assistant::factory()->create(['name' => 'Another Assistent']);

    Livewire::test(Assistants::class)
        ->assertSee('Test Assistent')
        ->assertSee('Another Assistent');
});

it('can create a new assistant', function () {
    Livewire::test(Assistants::class)
        ->set('createName', 'Ny Assistent')
        ->set('createEmployeeNumber', 42)
        ->set('createEmail', 'ny@example.com')
        ->set('createPhone', '12345678')
        ->set('createType', 'primary')
        ->set('createHiredAt', '2024-01-15')
        ->call('createAssistant')
        ->assertDispatched('close-modal')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('assistants', [
        'name' => 'Ny Assistent',
        'employee_number' => 42,
        'email' => 'ny@example.com',
    ]);
});

it('validates required fields when creating assistant', function () {
    Livewire::test(Assistants::class)
        ->set('createName', '')
        ->set('createEmployeeNumber', null)
        ->set('createEmail', '')
        ->call('createAssistant')
        ->assertHasErrors(['createName', 'createEmployeeNumber', 'createEmail']);
});

it('can update an assistant', function () {
    $assistant = Assistant::factory()->create(['name' => 'Original Name']);

    Livewire::test(Assistants::class)
        ->call('editAssistant', $assistant->id)
        ->set('editName', 'Updated Name')
        ->call('updateAssistant')
        ->assertDispatched('close-modal');

    $this->assertDatabaseHas('assistants', [
        'id' => $assistant->id,
        'name' => 'Updated Name',
    ]);
});

it('can soft delete an assistant', function () {
    $assistant = Assistant::factory()->create(['name' => 'To Delete']);

    Livewire::test(Assistants::class)
        ->call('deleteAssistant', $assistant->id);

    $this->assertSoftDeleted('assistants', ['id' => $assistant->id]);
});

it('can restore a soft deleted assistant', function () {
    $assistant = Assistant::factory()->create(['name' => 'Deleted']);
    $assistant->delete();

    Livewire::test(Assistants::class)
        ->set('showAll', true)
        ->call('restoreAssistant', $assistant->id);

    $this->assertDatabaseHas('assistants', [
        'id' => $assistant->id,
        'deleted_at' => null,
    ]);
});

it('can permanently delete an assistant', function () {
    $assistant = Assistant::factory()->create(['name' => 'To Force Delete']);
    $assistant->delete();

    Livewire::test(Assistants::class)
        ->set('showAll', true)
        ->call('forceDeleteAssistant', $assistant->id);

    $this->assertDatabaseMissing('assistants', ['id' => $assistant->id]);
});

it('shows deleted assistants when showAll is true', function () {
    $active = Assistant::factory()->create(['name' => 'Active One']);
    $deleted = Assistant::factory()->create(['name' => 'Deleted One']);
    $deleted->delete();

    Livewire::test(Assistants::class)
        ->assertSee('Active One')
        ->assertDontSee('Deleted One')
        ->set('showAll', true)
        ->assertSee('Active One')
        ->assertSee('Deleted One');
});

it('shows correct counts', function () {
    Assistant::factory()->count(3)->create();
    $deleted = Assistant::factory()->create();
    $deleted->delete();

    $component = Livewire::test(Assistants::class);

    expect($component->get('activeCount'))->toBe(3);
    expect($component->get('totalCount'))->toBe(4);
});
