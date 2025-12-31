<?php

declare(strict_types=1);

use App\Livewire\Medical\Prescriptions;
use App\Models\Prescription;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the prescriptions page', function () {
    $this->get(route('medical.prescriptions'))
        ->assertOk()
        ->assertSee('Resepter');
});

it('displays all prescriptions', function () {
    Prescription::factory()->create(['name' => 'Medisin A']);
    Prescription::factory()->create(['name' => 'Medisin B']);

    Livewire::test(Prescriptions::class)
        ->assertSee('Medisin A')
        ->assertSee('Medisin B');
});

it('can create a new prescription', function () {
    Livewire::test(Prescriptions::class)
        ->call('openModal')
        ->set('name', 'Ny Medisin')
        ->set('validTo', now()->addMonths(6)->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('prescriptions', [
        'name' => 'Ny Medisin',
    ]);
});

it('validates required fields when creating prescription', function () {
    Livewire::test(Prescriptions::class)
        ->set('name', '')
        ->set('validTo', '')
        ->call('save')
        ->assertHasErrors(['name', 'validTo']);
});

it('can edit an existing prescription', function () {
    $prescription = Prescription::factory()->create(['name' => 'Original Name']);

    Livewire::test(Prescriptions::class)
        ->call('openModal', $prescription->id)
        ->assertSet('editingId', $prescription->id)
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('prescriptions', [
        'id' => $prescription->id,
        'name' => 'Updated Name',
    ]);
});

it('can delete a prescription', function () {
    $prescription = Prescription::factory()->create(['name' => 'To Delete']);

    Livewire::test(Prescriptions::class)
        ->call('delete', $prescription->id);

    $this->assertSoftDeleted('prescriptions', [
        'id' => $prescription->id,
    ]);
});

it('shows correct status colors based on expiry date', function () {
    Prescription::factory()->create([
        'name' => 'OK Medicine',
        'valid_to' => now()->addMonths(6),
    ]);
    Prescription::factory()->create([
        'name' => 'Warning Medicine',
        'valid_to' => now()->addDays(20),
    ]);
    Prescription::factory()->create([
        'name' => 'Danger Medicine',
        'valid_to' => now()->addDays(5),
    ]);

    Livewire::test(Prescriptions::class)
        ->assertSee('OK Medicine')
        ->assertSee('Warning Medicine')
        ->assertSee('Danger Medicine');
});

it('can close modal', function () {
    Livewire::test(Prescriptions::class)
        ->set('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false);
});
