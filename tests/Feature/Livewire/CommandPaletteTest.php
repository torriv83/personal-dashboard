<?php

declare(strict_types=1);

use App\Livewire\CommandPalette;
use App\Models\Assistant;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Prescription;
use App\Models\TaskList;
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

it('searches bookmarks by title', function () {
    Bookmark::factory()->create(['title' => 'Laravel Documentation']);
    Bookmark::factory()->create(['title' => 'Vue.js Guide']);

    Livewire::test(CommandPalette::class)
        ->set('search', 'Laravel')
        ->assertSee('Laravel Documentation')
        ->assertDontSee('Vue.js Guide');
});

it('searches bookmarks by url', function () {
    Bookmark::factory()->create([
        'title' => 'My GitHub Profile',
        'url' => 'https://github.com/myusername',
    ]);
    Bookmark::factory()->create([
        'title' => 'Some Website',
        'url' => 'https://example.com',
    ]);

    Livewire::test(CommandPalette::class)
        ->set('search', 'github')
        ->assertSee('My GitHub Profile')
        ->assertDontSee('Some Website');
});

it('searches task lists by name', function () {
    TaskList::factory()->create(['name' => 'Grocery Shopping']);
    TaskList::factory()->create(['name' => 'House Cleaning']);

    Livewire::test(CommandPalette::class)
        ->set('search', 'Grocery')
        ->assertSee('Grocery Shopping')
        ->assertDontSee('House Cleaning');
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
    Bookmark::factory()->create(['title' => 'Test Bookmark']);
    TaskList::factory()->create(['name' => 'Test List']);

    $component = Livewire::test(CommandPalette::class)
        ->set('search', 'Test');

    $results = $component->get('results');
    $categories = $results->pluck('category')->unique();

    expect($categories)->toContain('Assistenter', 'Resepter', 'Bokmerker', 'Oppgaver');
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

it('has bookmark and task navigation quick actions', function () {
    $component = Livewire::test(CommandPalette::class);

    $quickActions = $component->get('quickActions');
    $bookmarkAction = collect($quickActions)->firstWhere('name', 'Gå til Bokmerker');
    $taskAction = collect($quickActions)->firstWhere('name', 'Gå til Oppgaver');

    expect($bookmarkAction)
        ->toHaveKey('url')
        ->toHaveKey('icon', 'bookmark')
        ->toHaveKey('category', 'Navigasjon');

    expect($taskAction)
        ->toHaveKey('url')
        ->toHaveKey('icon', 'check-square')
        ->toHaveKey('category', 'Navigasjon');
});

it('executes parallel search and merges results from all sources', function () {
    // Create test data in all 6 searchable models
    $category = Category::factory()->create();

    Assistant::factory()->create(['name' => 'Parallel Test Assistant']);
    Equipment::factory()->create(['name' => 'Parallel Test Equipment', 'category_id' => $category->id]);
    Prescription::factory()->create(['name' => 'Parallel Test Prescription']);
    WishlistItem::factory()->create(['name' => 'Parallel Test Wishlist']);
    Bookmark::factory()->create(['title' => 'Parallel Test Bookmark']);
    TaskList::factory()->create(['name' => 'Parallel Test TaskList']);

    $component = Livewire::test(CommandPalette::class)
        ->set('search', 'Parallel Test');

    $results = $component->get('results');

    // Verify results come from all 6 search sources
    $categories = $results->pluck('category')->unique()->sort()->values();

    expect($categories)->toContain('Assistenter')
        ->and($categories)->toContain('Utstyr')
        ->and($categories)->toContain('Resepter')
        ->and($categories)->toContain('Ønskeliste')
        ->and($categories)->toContain('Bokmerker')
        ->and($categories)->toContain('Oppgaver')
        ->and($results->count())->toBe(6);
});

it('respects 15-item limit with parallel execution', function () {
    // Create more than 15 items across different models to test the limit
    $category = Category::factory()->create();

    Assistant::factory()->count(6)->create(['name' => 'Limit Test Assistant']);
    Equipment::factory()->count(6)->create(['name' => 'Limit Test Equipment', 'category_id' => $category->id]);
    Prescription::factory()->count(6)->create(['name' => 'Limit Test Prescription']);
    WishlistItem::factory()->count(6)->create(['name' => 'Limit Test Wishlist']);

    $component = Livewire::test(CommandPalette::class)
        ->set('search', 'Limit Test');

    $results = $component->get('results');

    // With 4 models × 6 items each = 24 items, but should be limited to 15
    expect($results->count())->toBeLessThanOrEqual(15);
});

it('filters quick actions correctly with parallel search', function () {
    // Create database items that would match a search term
    $category = Category::factory()->create();
    Assistant::factory()->create(['name' => 'Kalender Assistant']);
    Equipment::factory()->create(['name' => 'Kalender Equipment', 'category_id' => $category->id]);

    $component = Livewire::test(CommandPalette::class)
        ->set('search', 'kalender');

    $results = $component->get('results');

    // Should include the quick action "Gå til Kalender"
    $quickAction = $results->firstWhere('name', 'Gå til Kalender');
    expect($quickAction)->not->toBeNull()
        ->and($quickAction['category'])->toBe('Navigasjon');

    // Should NOT include non-matching quick actions
    $profileAction = $results->firstWhere('name', 'Gå til Profil');
    expect($profileAction)->toBeNull();

    // Should also include database results
    $categories = $results->pluck('category')->unique();
    expect($categories)->toContain('Assistenter')
        ->and($categories)->toContain('Utstyr');
});

it('handles empty search results gracefully', function () {
    // Search for a term that won't match anything
    $component = Livewire::test(CommandPalette::class)
        ->set('search', 'xyznonexistentterm123');

    $results = $component->get('results');

    // Should return an empty collection when nothing matches
    expect($results->count())->toBe(0);
});

it('handles partial empty results from some sources', function () {
    // Create data in only some models
    $category = Category::factory()->create();
    Assistant::factory()->create(['name' => 'Partial Test Assistant']);
    Equipment::factory()->create(['name' => 'Partial Test Equipment', 'category_id' => $category->id]);
    // No Prescriptions, WishlistItems, Bookmarks, or TaskLists created

    $component = Livewire::test(CommandPalette::class)
        ->set('search', 'Partial Test');

    $results = $component->get('results');

    // Should only have results from the 2 models that have data
    expect($results->count())->toBe(2);

    $categories = $results->pluck('category')->unique()->sort()->values();
    expect($categories)->toContain('Assistenter')
        ->and($categories)->toContain('Utstyr')
        ->and($categories)->not->toContain('Resepter')
        ->and($categories)->not->toContain('Ønskeliste')
        ->and($categories)->not->toContain('Bokmerker')
        ->and($categories)->not->toContain('Oppgaver');
});

it('maintains result order priority with parallel execution', function () {
    // Create items that should appear in specific order based on merge logic
    $category = Category::factory()->create();

    Assistant::factory()->create(['name' => 'Order Test Item']);
    Equipment::factory()->create(['name' => 'Order Test Item', 'category_id' => $category->id]);
    Prescription::factory()->create(['name' => 'Order Test Item']);

    $component = Livewire::test(CommandPalette::class)
        ->set('search', 'Order Test');

    $results = $component->get('results');

    // Results should be merged in the order: quickActions, assistants, equipment, prescriptions, wishlist, bookmarks, tasks
    // First result should be from Assistants (after quick actions are filtered out)
    expect($results->first()['category'])->toBe('Assistenter')
        ->and($results->get(1)['category'])->toBe('Utstyr')
        ->and($results->get(2)['category'])->toBe('Resepter');
});
