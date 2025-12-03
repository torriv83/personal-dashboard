<?php

use App\Livewire\Medical\Dashboard;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Prescription;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the medical dashboard', function () {
    $this->get(route('medical.dashboard'))
        ->assertOk()
        ->assertSee('Medisinsk');
});

it('shows correct stats', function () {
    Category::factory()->count(3)->create();
    Equipment::factory()->count(5)->create();
    Prescription::factory()->count(7)->create();

    Livewire::test(Dashboard::class)
        ->assertSee('5')
        ->assertSee('7');
});

it('shows expiring prescriptions within 30 days', function () {
    Prescription::factory()->create([
        'name' => 'Expiring Soon Medicine',
        'valid_to' => now()->addDays(5),
    ]);
    Prescription::factory()->create([
        'name' => 'Far Future Medicine',
        'valid_to' => now()->addDays(60),
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Expiring Soon Medicine')
        ->assertDontSee('Far Future Medicine');
});

it('shows next expiry alert for most critical prescription', function () {
    Prescription::factory()->create([
        'name' => 'Later Expiry',
        'valid_to' => now()->addDays(20),
    ]);
    Prescription::factory()->create([
        'name' => 'Critical Expiry',
        'valid_to' => now()->addDays(3),
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Critical Expiry')
        ->assertSee('3');
});

it('shows expired prescriptions', function () {
    Prescription::factory()->expired()->create([
        'name' => 'Expired Medicine',
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Expired Medicine')
        ->assertSee('Utl');
});

it('shows quick links to equipment and prescriptions', function () {
    Livewire::test(Dashboard::class)
        ->assertSee('Utstyr')
        ->assertSee('Resepter');
});
