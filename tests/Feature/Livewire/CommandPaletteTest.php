<?php

use App\Livewire\CommandPalette;
use App\Models\Assistant;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Prescription;
use App\Models\User;
use App\Models\WishlistItem;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the command palette component', function () {
    Livewire::test(CommandPalette::class)
        ->assertStatus(200);
});

it('starts closed by default', function () {
    Livewire::test(CommandPalette::class)
        ->assertSet('isOpen', false)
        ->assertSet('search', '');
});

it('can open the command palette', function () {
    Livewire::test(CommandPalette::class)
        ->call('open')
        ->assertSet('isOpen', true)
        ->assertSet('search', '');
});

it('can close the command palette', function () {
    Livewire::test(CommandPalette::class)
        ->set('isOpen', true)
        ->set('search', 'test')
        ->call('close')
        ->assertSet('isOpen', false)
        ->assertSet('search', '');
});

it('shows quick actions when search is empty', function () {
    Livewire::test(CommandPalette::class)
        ->assertSee('Ny vakt')
        ->assertSee('Gå til Dashboard')
        ->assertSee('Portvelger');
});

it('shows quick actions when search is less than 2 characters', function () {
    Livewire::test(CommandPalette::class)
        ->set('search', 'a')
        ->assertSee('Ny vakt')
        ->assertSee('Gå til Dashboard');
});

it('filters quick actions by search term', function () {
    Livewire::test(CommandPalette::class)
        ->set('search', 'kalender')
        ->assertSee('Gå til Kalender')
        ->assertDontSee('Gå til Profil');
});

it('searches assistants by name', function () {
    Assistant::factory()->create(['name' => 'Ola Nordmann']);
    Assistant::factory()->create(['name' => 'Kari Hansen']);

    Livewire::test(CommandPalette::class)
        ->set('search', 'Ola')
        ->assertSee('Ola Nordmann')
        ->assertDontSee('Kari Hansen');
});

it('searches assistants by employee number', function () {
    Assistant::factory()->create(['name' => 'Test Person', 'employee_number' => 12345]);

    Livewire::test(CommandPalette::class)
        ->set('search', '12345')
        ->assertSee('Test Person');
});

it('searches equipment by name', function () {
    $category = Category::factory()->create();
    Equipment::factory()->create(['name' => 'Rullestol', 'category_id' => $category->id]);
    Equipment::factory()->create(['name' => 'Seng', 'category_id' => $category->id]);

    Livewire::test(CommandPalette::class)
        ->set('search', 'Rulle')
        ->assertSee('Rullestol')
        ->assertDontSee('Seng');
});

it('searches equipment by article number', function () {
    $category = Category::factory()->create();
    Equipment::factory()->create([
        'name' => 'Test Utstyr',
        'article_number' => 'ART-999',
        'category_id' => $category->id,
    ]);

    Livewire::test(CommandPalette::class)
        ->set('search', 'ART-999')
        ->assertSee('Test Utstyr');
});

it('searches prescriptions by name', function () {
    Prescription::factory()->create(['name' => 'Paracet']);
    Prescription::factory()->create(['name' => 'Ibux']);

    Livewire::test(CommandPalette::class)
        ->set('search', 'Para')
        ->assertSee('Paracet')
        ->assertDontSee('Ibux');
});

it('searches wishlist items by name', function () {
    WishlistItem::factory()->create(['name' => 'PlayStation 5']);
    WishlistItem::factory()->create(['name' => 'Xbox']);

    Livewire::test(CommandPalette::class)
        ->set('search', 'Play')
        ->assertSee('PlayStation 5')
        ->assertDontSee('Xbox');
});

it('limits results to 15 items total', function () {
    // Each model search is limited to 5, total capped at 15
    // Create enough items to exceed the limit
    Assistant::factory()->count(10)->create(['name' => 'Søk Person']);
    Prescription::factory()->count(10)->create(['name' => 'Søk Medisin']);
    WishlistItem::factory()->count(10)->create(['name' => 'Søk Ønske']);
    $category = Category::factory()->create();
    Equipment::factory()->count(10)->create(['name' => 'Søk Utstyr', 'category_id' => $category->id]);

    $component = Livewire::test(CommandPalette::class)
        ->set('search', 'Søk');

    // Max 5 per model = up to 20 results, but capped at 15
    expect($component->get('results')->count())->toBeLessThanOrEqual(15);
});

it('includes results from multiple categories', function () {
    Assistant::factory()->create(['name' => 'Test Assistent']);
    Prescription::factory()->create(['name' => 'Test Medisin']);

    $component = Livewire::test(CommandPalette::class)
        ->set('search', 'Test');

    $results = $component->get('results');
    $categories = $results->pluck('category')->unique();

    expect($categories)->toContain('Assistenter', 'Resepter');
});

it('has correct quick action categories', function () {
    $component = Livewire::test(CommandPalette::class);

    $quickActions = $component->get('quickActions');
    $categories = collect($quickActions)->pluck('category')->unique()->values()->toArray();

    expect($categories)->toContain('Handlinger', 'Navigasjon', 'Verktøy');
});

it('has create actions with create=1 parameter', function () {
    $component = Livewire::test(CommandPalette::class);

    $quickActions = $component->get('quickActions');
    $createActions = collect($quickActions)
        ->filter(fn ($a) => isset($a['url']) && str_contains($a['url'], 'create=1'));

    expect($createActions)->toHaveCount(5);
});

it('can start weight registration action', function () {
    Livewire::test(CommandPalette::class)
        ->call('startWeightRegistration')
        ->assertSet('actionMode', 'weight')
        ->assertSet('weightInput', '');
});

it('can cancel weight registration action', function () {
    Livewire::test(CommandPalette::class)
        ->set('actionMode', 'weight')
        ->set('weightInput', '75.5')
        ->call('cancelAction')
        ->assertSet('actionMode', null)
        ->assertSet('weightInput', '');
});

it('can save weight from command palette', function () {
    Livewire::test(CommandPalette::class)
        ->call('startWeightRegistration')
        ->set('weightInput', '75.5')
        ->call('saveWeight')
        ->assertSet('actionMode', null)
        ->assertSet('isOpen', false)
        ->assertDispatched('toast');

    $this->assertDatabaseHas('weight_entries', [
        'weight' => 75.5,
    ]);
});

it('validates weight input', function () {
    Livewire::test(CommandPalette::class)
        ->call('startWeightRegistration')
        ->set('weightInput', '15')
        ->call('saveWeight')
        ->assertHasErrors(['weightInput' => 'min']);
});

it('has weight action in quick actions', function () {
    $component = Livewire::test(CommandPalette::class);

    $quickActions = $component->get('quickActions');
    $weightAction = collect($quickActions)->firstWhere('name', 'Registrer vekt');

    expect($weightAction)
        ->toHaveKey('action', 'weight')
        ->not->toHaveKey('url');
});
