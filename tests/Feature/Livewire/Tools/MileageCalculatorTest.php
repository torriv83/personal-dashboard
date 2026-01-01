<?php

declare(strict_types=1);

use App\Livewire\Tools\MileageCalculator;
use App\Models\MileageDestination;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// ====================
// Page Access
// ====================

test('can access mileage calculator page', function () {
    $this->get(route('tools.mileage-calculator'))
        ->assertOk()
        ->assertSeeLivewire(MileageCalculator::class);
});

test('guests cannot access mileage calculator page', function () {
    auth()->logout();

    $this->get(route('tools.mileage-calculator'))
        ->assertRedirect(route('login'));
});

// ====================
// Destination CRUD
// ====================

test('can add destination with valid data', function () {
    Setting::set('mileage_home_address', 'Halden, Norway');

    // Mock the OpenRouteService HTTP calls
    Http::fake([
        'api.openrouteservice.org/geocode/search*' => Http::sequence()
            ->push([
                'features' => [
                    ['geometry' => ['coordinates' => [11.3875, 59.1229]]],
                ],
            ])
            ->push([
                'features' => [
                    ['geometry' => ['coordinates' => [10.7522, 59.9139]]],
                ],
            ]),
        'api.openrouteservice.org/v2/directions/driving-car*' => Http::response([
            'features' => [
                [
                    'properties' => [
                        'segments' => [
                            ['distance' => 123456.78], // meters
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    Livewire::test(MileageCalculator::class)
        ->set('newDestinationName', 'Oslo')
        ->set('newDestinationAddress', 'Oslo, Norway')
        ->call('addDestination')
        ->assertDispatched('toast', message: 'Destinasjon lagt til', type: 'success');

    $destination = MileageDestination::first();
    expect($destination)->not->toBeNull();
    expect($destination->name)->toBe('Oslo');
    expect($destination->address)->toBe('Oslo, Norway');
    expect($destination->distance_km)->toBe('123.46');
    expect($destination->sort_order)->toBe(0);
});

test('can delete destination', function () {
    $destination = MileageDestination::factory()->create();

    expect(MileageDestination::count())->toBe(1);

    Livewire::test(MileageCalculator::class)
        ->call('deleteDestination', $destination->id)
        ->assertDispatched('toast', message: 'Destinasjon slettet', type: 'success');

    expect(MileageDestination::count())->toBe(0);
});

test('addDestination validates required name', function () {
    Setting::set('mileage_home_address', 'Halden, Norway');

    Livewire::test(MileageCalculator::class)
        ->set('newDestinationName', '')
        ->set('newDestinationAddress', 'Oslo, Norway')
        ->call('addDestination')
        ->assertHasErrors(['newDestinationName' => 'required']);
});

test('addDestination validates required address', function () {
    Setting::set('mileage_home_address', 'Halden, Norway');

    Livewire::test(MileageCalculator::class)
        ->set('newDestinationName', 'Oslo')
        ->set('newDestinationAddress', '')
        ->call('addDestination')
        ->assertHasErrors(['newDestinationAddress' => 'required']);
});

test('addDestination validates max length for name', function () {
    Setting::set('mileage_home_address', 'Halden, Norway');

    Livewire::test(MileageCalculator::class)
        ->set('newDestinationName', str_repeat('a', 256))
        ->set('newDestinationAddress', 'Oslo, Norway')
        ->call('addDestination')
        ->assertHasErrors(['newDestinationName' => 'max']);
});

test('addDestination validates max length for address', function () {
    Setting::set('mileage_home_address', 'Halden, Norway');

    Livewire::test(MileageCalculator::class)
        ->set('newDestinationName', 'Oslo')
        ->set('newDestinationAddress', str_repeat('a', 256))
        ->call('addDestination')
        ->assertHasErrors(['newDestinationAddress' => 'max']);
});

test('addDestination fails when home address is not set', function () {
    Livewire::test(MileageCalculator::class)
        ->set('newDestinationName', 'Oslo')
        ->set('newDestinationAddress', 'Oslo, Norway')
        ->call('addDestination')
        ->assertDispatched('toast', message: 'Du må lagre en hjemmeadresse først', type: 'error');
});

test('addDestination fails when address cannot be found', function () {
    Setting::set('mileage_home_address', 'Halden, Norway');

    // Mock the OpenRouteService HTTP calls to return null (address not found)
    Http::fake([
        'api.openrouteservice.org/geocode/search*' => Http::response([
            'features' => [],
        ]),
    ]);

    Livewire::test(MileageCalculator::class)
        ->set('newDestinationName', 'Invalid Address')
        ->set('newDestinationAddress', 'Invalid Address 123')
        ->call('addDestination')
        ->assertDispatched('toast', message: 'Kunne ikke finne adressen. Prøv en mer spesifikk adresse.', type: 'error');
});

test('addDestination handles OpenRouteService exception', function () {
    Setting::set('mileage_home_address', 'Halden, Norway');

    // Mock the OpenRouteService HTTP calls to throw an exception
    Http::fake([
        'api.openrouteservice.org/geocode/search*' => Http::response(null, 500),
    ]);

    Livewire::test(MileageCalculator::class)
        ->set('newDestinationName', 'Oslo')
        ->set('newDestinationAddress', 'Oslo, Norway')
        ->call('addDestination')
        ->assertDispatched('toast', type: 'error');
});

// ====================
// Distance Calculation
// ====================

test('getDisplayDistance returns correct distance for round-trip mode', function () {
    $component = Livewire::test(MileageCalculator::class)
        ->set('roundTrip', true);

    $displayDistance = $component->instance()->getDisplayDistance(50.0);

    expect($displayDistance)->toBe(100.0);
});

test('getDisplayDistance returns correct distance for one-way mode', function () {
    $component = Livewire::test(MileageCalculator::class)
        ->set('roundTrip', false);

    $displayDistance = $component->instance()->getDisplayDistance(50.0);

    expect($displayDistance)->toBe(50.0);
});

test('can recalculate distance for existing destination', function () {
    Setting::set('mileage_home_address', 'Halden, Norway');

    $destination = MileageDestination::factory()->create([
        'name' => 'Oslo',
        'address' => 'Oslo, Norway',
        'distance_km' => '100.00',
    ]);

    // Mock the OpenRouteService HTTP calls
    Http::fake([
        'api.openrouteservice.org/geocode/search*' => Http::sequence()
            ->push([
                'features' => [
                    ['geometry' => ['coordinates' => [11.3875, 59.1229]]],
                ],
            ])
            ->push([
                'features' => [
                    ['geometry' => ['coordinates' => [10.7522, 59.9139]]],
                ],
            ]),
        'api.openrouteservice.org/v2/directions/driving-car*' => Http::response([
            'features' => [
                [
                    'properties' => [
                        'segments' => [
                            ['distance' => 150000.0], // 150 km in meters
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    Livewire::test(MileageCalculator::class)
        ->call('recalculateDistance', $destination->id)
        ->assertDispatched('toast', message: 'Avstand oppdatert', type: 'success');

    $destination->refresh();
    expect($destination->distance_km)->toBe('150.00');
});

test('recalculateDistance fails when home address is not set', function () {
    $destination = MileageDestination::factory()->create();

    Livewire::test(MileageCalculator::class)
        ->call('recalculateDistance', $destination->id)
        ->assertDispatched('toast', message: 'Du må lagre en hjemmeadresse først', type: 'error');
});

test('recalculateDistance fails when address cannot be found', function () {
    Setting::set('mileage_home_address', 'Halden, Norway');

    $destination = MileageDestination::factory()->create([
        'address' => 'Invalid Address 123',
    ]);

    // Mock the OpenRouteService HTTP calls to return null (address not found)
    Http::fake([
        'api.openrouteservice.org/geocode/search*' => Http::response([
            'features' => [],
        ]),
    ]);

    Livewire::test(MileageCalculator::class)
        ->call('recalculateDistance', $destination->id)
        ->assertDispatched('toast', message: 'Kunne ikke finne adressen. Prøv å oppdatere til en mer spesifikk adresse.', type: 'error');
});

test('recalculateDistance handles OpenRouteService exception', function () {
    Setting::set('mileage_home_address', 'Halden, Norway');

    $destination = MileageDestination::factory()->create();

    // Mock the OpenRouteService HTTP calls to throw an exception
    Http::fake([
        'api.openrouteservice.org/geocode/search*' => Http::response(null, 500),
    ]);

    Livewire::test(MileageCalculator::class)
        ->call('recalculateDistance', $destination->id)
        ->assertDispatched('toast', type: 'error');
});

// ====================
// Destination Reordering
// ====================

test('can reorder destinations', function () {
    $destination1 = MileageDestination::factory()->create(['name' => 'First', 'sort_order' => 0]);
    $destination2 = MileageDestination::factory()->create(['name' => 'Second', 'sort_order' => 1]);
    $destination3 = MileageDestination::factory()->create(['name' => 'Third', 'sort_order' => 2]);

    // Move first destination to last position (position 2)
    Livewire::test(MileageCalculator::class)
        ->call('updateOrder', (string) $destination1->id, 2);

    $destination1->refresh();
    $destination2->refresh();
    $destination3->refresh();

    expect($destination2->sort_order)->toBe(0);
    expect($destination3->sort_order)->toBe(1);
    expect($destination1->sort_order)->toBe(2);
});

test('can reorder destination to first position', function () {
    $destination1 = MileageDestination::factory()->create(['name' => 'First', 'sort_order' => 0]);
    $destination2 = MileageDestination::factory()->create(['name' => 'Second', 'sort_order' => 1]);
    $destination3 = MileageDestination::factory()->create(['name' => 'Third', 'sort_order' => 2]);

    // Move last destination to first position (position 0)
    Livewire::test(MileageCalculator::class)
        ->call('updateOrder', (string) $destination3->id, 0);

    $destination1->refresh();
    $destination2->refresh();
    $destination3->refresh();

    expect($destination3->sort_order)->toBe(0);
    expect($destination1->sort_order)->toBe(1);
    expect($destination2->sort_order)->toBe(2);
});

test('can reorder destination to middle position', function () {
    $destination1 = MileageDestination::factory()->create(['name' => 'First', 'sort_order' => 0]);
    $destination2 = MileageDestination::factory()->create(['name' => 'Second', 'sort_order' => 1]);
    $destination3 = MileageDestination::factory()->create(['name' => 'Third', 'sort_order' => 2]);

    // Move first destination to middle position (position 1)
    Livewire::test(MileageCalculator::class)
        ->call('updateOrder', (string) $destination1->id, 1);

    $destination1->refresh();
    $destination2->refresh();
    $destination3->refresh();

    expect($destination2->sort_order)->toBe(0);
    expect($destination1->sort_order)->toBe(1);
    expect($destination3->sort_order)->toBe(2);
});

test('updateOrder maintains correct order with multiple destinations', function () {
    $destinations = [];
    for ($i = 0; $i < 5; $i++) {
        $destinations[] = MileageDestination::factory()->create([
            'name' => "Destination {$i}",
            'sort_order' => $i,
        ]);
    }

    // Move destination at position 1 to position 3
    Livewire::test(MileageCalculator::class)
        ->call('updateOrder', (string) $destinations[1]->id, 3);

    // Refresh all destinations
    foreach ($destinations as $destination) {
        $destination->refresh();
    }

    // Expected order: 0, 2, 3, 1, 4
    expect($destinations[0]->sort_order)->toBe(0);
    expect($destinations[2]->sort_order)->toBe(1);
    expect($destinations[3]->sort_order)->toBe(2);
    expect($destinations[1]->sort_order)->toBe(3);
    expect($destinations[4]->sort_order)->toBe(4);
});
