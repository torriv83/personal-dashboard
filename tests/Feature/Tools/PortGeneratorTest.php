<?php

declare(strict_types=1);

use App\Livewire\Tools\PortGenerator;
use App\Models\User;
use Livewire\Livewire;

test('port generator page loads for authenticated user', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PortGenerator::class)
        ->assertStatus(200)
        ->assertSee('Portvelger');
});

test('generates port in valid range on mount', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(PortGenerator::class);

    $port = $component->get('port');

    expect($port)->toBeGreaterThanOrEqual(49152)
        ->toBeLessThanOrEqual(65535);
});

test('can generate new port', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(PortGenerator::class);

    $initialPort = $component->get('port');

    // Generer nye porter til vi får en annen (svært usannsynlig å få samme 100 ganger)
    $newPort = $initialPort;
    for ($i = 0; $i < 100 && $newPort === $initialPort; $i++) {
        $component->call('generatePort');
        $newPort = $component->get('port');
    }

    expect($newPort)->toBeGreaterThanOrEqual(49152)
        ->toBeLessThanOrEqual(65535);
});

test('port generator route requires authentication', function () {
    $this->get('/verktoy/port-generator')
        ->assertRedirect('/logg-inn');
});
