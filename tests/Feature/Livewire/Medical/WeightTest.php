<?php

declare(strict_types=1);

use App\Livewire\Medical\Weight;
use App\Models\User;
use App\Models\WeightEntry;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the weight page', function () {
    $this->get(route('medical.weight'))
        ->assertOk()
        ->assertSee('Vekt');
});

it('renders the weight component', function () {
    Livewire::test(Weight::class)
        ->assertStatus(200)
        ->assertSee('Vekt');
});

it('shows empty state when no entries exist', function () {
    Livewire::test(Weight::class)
        ->assertSee('Ingen vektregistreringer enda');
});

it('displays existing weight entries', function () {
    WeightEntry::factory()->create([
        'recorded_at' => now(),
        'weight' => 75.5,
    ]);

    Livewire::test(Weight::class)
        ->assertSee('75,5');
});

it('can open modal for new entry', function () {
    Livewire::test(Weight::class)
        ->call('openModal')
        ->assertSet('showModal', true)
        ->assertSet('editingId', null);
});

it('can close modal', function () {
    Livewire::test(Weight::class)
        ->call('openModal')
        ->assertSet('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false);
});

it('can create a new weight entry', function () {
    Livewire::test(Weight::class)
        ->call('openModal')
        ->set('date', '2024-12-06')
        ->set('time', '08:30')
        ->set('weight', '75.5')
        ->set('note', 'Morgen')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $this->assertDatabaseHas('weight_entries', [
        'weight' => 75.5,
        'note' => 'Morgen',
    ]);
});

it('validates required fields when creating entry', function () {
    Livewire::test(Weight::class)
        ->call('openModal')
        ->set('date', '')
        ->set('time', '')
        ->set('weight', '')
        ->call('save')
        ->assertHasErrors(['date', 'time', 'weight']);
});

it('validates weight minimum value', function () {
    Livewire::test(Weight::class)
        ->call('openModal')
        ->set('date', '2024-12-06')
        ->set('time', '08:30')
        ->set('weight', '10')
        ->call('save')
        ->assertHasErrors(['weight']);
});

it('validates weight maximum value', function () {
    Livewire::test(Weight::class)
        ->call('openModal')
        ->set('date', '2024-12-06')
        ->set('time', '08:30')
        ->set('weight', '400')
        ->call('save')
        ->assertHasErrors(['weight']);
});

it('can edit an existing weight entry', function () {
    $entry = WeightEntry::factory()->create([
        'recorded_at' => now(),
        'weight' => 75.5,
        'note' => 'Original',
    ]);

    Livewire::test(Weight::class)
        ->call('openModal', $entry->id)
        ->assertSet('editingId', $entry->id)
        ->assertSet('weight', '75.50')
        ->set('weight', '76.0')
        ->set('note', 'Updated')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('weight_entries', [
        'id' => $entry->id,
        'weight' => 76.0,
        'note' => 'Updated',
    ]);
});

it('can delete a weight entry', function () {
    $entry = WeightEntry::factory()->create([
        'recorded_at' => now(),
        'weight' => 75.5,
    ]);

    Livewire::test(Weight::class)
        ->call('delete', $entry->id);

    $this->assertDatabaseMissing('weight_entries', [
        'id' => $entry->id,
    ]);
});

it('allows multiple entries per day', function () {
    // Create morning entry
    Livewire::test(Weight::class)
        ->call('openModal')
        ->set('date', '2024-12-06')
        ->set('time', '08:00')
        ->set('weight', '75.5')
        ->call('save')
        ->assertHasNoErrors();

    // Create evening entry for same day
    Livewire::test(Weight::class)
        ->call('openModal')
        ->set('date', '2024-12-06')
        ->set('time', '20:00')
        ->set('weight', '76.0')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseCount('weight_entries', 2);
});

it('calculates correct stats', function () {
    WeightEntry::factory()->create(['recorded_at' => now()->subDays(2), 'weight' => 74.0]);
    WeightEntry::factory()->create(['recorded_at' => now()->subDay(), 'weight' => 75.0]);
    WeightEntry::factory()->create(['recorded_at' => now(), 'weight' => 76.0]);

    Livewire::test(Weight::class)
        ->assertSee('76,0') // Current weight
        ->assertSee('74,0') // Min weight
        ->assertSee('kilogram');
});

it('shows chart when multiple entries exist', function () {
    WeightEntry::factory()->create(['recorded_at' => now()->subDay(), 'weight' => 75.0]);
    WeightEntry::factory()->create(['recorded_at' => now(), 'weight' => 76.0]);

    Livewire::test(Weight::class)
        ->assertSee('Vektutvikling');
});

it('hides chart when only one entry exists', function () {
    WeightEntry::factory()->create(['recorded_at' => now(), 'weight' => 75.0]);

    Livewire::test(Weight::class)
        ->assertDontSee('Vektutvikling');
});

it('orders entries by recorded_at descending', function () {
    $older = WeightEntry::factory()->create(['recorded_at' => now()->subDays(2), 'weight' => 74.0]);
    $latest = WeightEntry::factory()->create(['recorded_at' => now(), 'weight' => 76.0]);

    // The latest entry (76.0) should appear first in the list
    $component = Livewire::test(Weight::class);
    $html = $component->html();

    // The latest weight should appear before the older weight in the DOM
    $latestPos = strpos($html, '76,0');
    $olderPos = strpos($html, '74,0');

    expect($latestPos)->toBeLessThan($olderPos);
});

it('resets form after closing modal', function () {
    Livewire::test(Weight::class)
        ->call('openModal')
        ->set('weight', '75.5')
        ->set('note', 'Test')
        ->call('closeModal')
        ->assertSet('weight', '')
        ->assertSet('note', '');
});
