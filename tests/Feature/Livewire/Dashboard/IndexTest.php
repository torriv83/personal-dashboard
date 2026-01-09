<?php

declare(strict_types=1);

use App\Livewire\Dashboard\Index;
use App\Models\Assistant;
use App\Models\Prescription;
use App\Models\Shift;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\YnabService;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the dashboard page', function () {
    $this->get(route('dashboard'))
        ->assertOk();
});

it('renders the dashboard component', function () {
    Livewire::test(Index::class)
        ->assertStatus(200);
});

it('shows next shift when upcoming shift exists', function () {
    $assistant = Assistant::factory()->create(['name' => 'Test Assistent']);

    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addDays(2)->addHours(3),
        'is_unavailable' => false,
    ]);

    $component = Livewire::test(Index::class);
    $nextShift = $component->get('nextShift');

    expect($nextShift)->not->toBeNull();
    expect($nextShift->assistant->name)->toBe('Test Assistent');
});

it('returns null for next shift when no upcoming shifts', function () {
    $component = Livewire::test(Index::class);
    $nextShift = $component->get('nextShift');

    expect($nextShift)->toBeNull();
});

it('does not include unavailable shifts in next shift', function () {
    $assistant = Assistant::factory()->create();

    // Create only unavailable shift
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->addDays(1),
        'ends_at' => now()->addDays(1)->addHours(3),
        'is_unavailable' => true,
    ]);

    $component = Livewire::test(Index::class);
    $nextShift = $component->get('nextShift');

    expect($nextShift)->toBeNull();
});

it('does not include past shifts in next shift', function () {
    $assistant = Assistant::factory()->create();

    // Create only past shift
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDays(2)->addHours(3),
        'is_unavailable' => false,
    ]);

    $component = Livewire::test(Index::class);
    $nextShift = $component->get('nextShift');

    expect($nextShift)->toBeNull();
});

it('returns correct count of expiring prescriptions', function () {
    // Create prescription expiring within 60 days
    Prescription::factory()->create([
        'valid_to' => now()->addDays(30),
    ]);
    Prescription::factory()->create([
        'valid_to' => now()->addDays(45),
    ]);

    // Create prescription NOT expiring within 60 days
    Prescription::factory()->create([
        'valid_to' => now()->addDays(90),
    ]);

    // Create already expired prescription
    Prescription::factory()->create([
        'valid_to' => now()->subDays(5),
    ]);

    $component = Livewire::test(Index::class);
    $count = $component->get('expiringPrescriptionsCount');

    expect($count)->toBe(2);
});

it('returns zero expiring prescriptions when none exist', function () {
    $component = Livewire::test(Index::class);
    $count = $component->get('expiringPrescriptionsCount');

    expect($count)->toBe(0);
});

it('returns correct wishlist count excluding saved and purchased', function () {
    // Create active wishlist items (waiting and saving should be counted)
    WishlistItem::factory()->create(['status' => 'waiting']);
    WishlistItem::factory()->create(['status' => 'saving']);

    // Create saved/purchased items that should be excluded
    WishlistItem::factory()->create(['status' => 'saved']);
    WishlistItem::factory()->create(['status' => 'purchased']);

    $component = Livewire::test(Index::class);
    $count = $component->get('wishlistCount');

    expect($count)->toBe(2);
});

it('returns zero wishlist count when all items are saved or purchased', function () {
    WishlistItem::factory()->create(['status' => 'saved']);
    WishlistItem::factory()->create(['status' => 'purchased']);

    $component = Livewire::test(Index::class);
    $count = $component->get('wishlistCount');

    expect($count)->toBe(0);
});

it('returns null for to be budgeted when YNAB is not configured', function () {
    $mock = Mockery::mock(YnabService::class);
    $mock->shouldReceive('isConfigured')->andReturn(false);
    app()->instance(YnabService::class, $mock);

    $component = Livewire::test(Index::class);
    $toBeBudgeted = $component->get('toBeBudgeted');

    expect($toBeBudgeted)->toBeNull();
});

it('returns to be budgeted when YNAB is configured', function () {
    $mock = Mockery::mock(YnabService::class);
    $mock->shouldReceive('isConfigured')->andReturn(true);
    $mock->shouldReceive('getMonthlyData')->with(1)->andReturn([
        ['to_be_budgeted' => 5000.50],
    ]);
    app()->instance(YnabService::class, $mock);

    $component = Livewire::test(Index::class);
    $toBeBudgeted = $component->get('toBeBudgeted');

    expect($toBeBudgeted)->toBe(5000.50);
});

it('returns null when YNAB monthly data is empty', function () {
    $mock = Mockery::mock(YnabService::class);
    $mock->shouldReceive('isConfigured')->andReturn(true);
    $mock->shouldReceive('getMonthlyData')->with(1)->andReturn([]);
    app()->instance(YnabService::class, $mock);

    $component = Livewire::test(Index::class);
    $toBeBudgeted = $component->get('toBeBudgeted');

    expect($toBeBudgeted)->toBeNull();
});

it('gets earliest upcoming shift when multiple exist', function () {
    $assistant = Assistant::factory()->create();

    $earlierShift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->addDays(1),
        'ends_at' => now()->addDays(1)->addHours(3),
        'is_unavailable' => false,
    ]);

    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHours(3),
        'is_unavailable' => false,
    ]);

    $component = Livewire::test(Index::class);
    $nextShift = $component->get('nextShift');

    expect($nextShift->id)->toBe($earlierShift->id);
});

it('eager loads assistant relation on next shift', function () {
    $assistant = Assistant::factory()->create(['name' => 'Eager Load Test']);

    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => now()->addDays(1),
        'ends_at' => now()->addDays(1)->addHours(3),
        'is_unavailable' => false,
    ]);

    $component = Livewire::test(Index::class);
    $nextShift = $component->get('nextShift');

    // Should have loaded assistant without additional query
    expect($nextShift->relationLoaded('assistant'))->toBeTrue();
    expect($nextShift->assistant->name)->toBe('Eager Load Test');
});
