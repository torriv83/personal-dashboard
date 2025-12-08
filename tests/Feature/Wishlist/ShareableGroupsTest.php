<?php

use App\Livewire\Wishlist\Index;
use App\Livewire\Wishlist\SharedView;
use App\Models\User;
use App\Models\WishlistGroup;
use App\Models\WishlistItem;
use Livewire\Livewire;

test('can enable sharing on a group', function () {
    $user = User::factory()->create();
    $group = WishlistGroup::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openShareModal', $group->id)
        ->assertSet('showShareModal', true)
        ->assertSet('sharingEnabled', false)
        ->call('toggleSharing')
        ->assertSet('sharingEnabled', true);

    $group->refresh();
    expect($group->is_shared)->toBeTrue()
        ->and($group->share_token)->not->toBeNull();
});

test('can disable sharing on a group', function () {
    $user = User::factory()->create();
    $group = WishlistGroup::factory()->create([
        'is_shared' => true,
        'share_token' => 'test-token-12345678901234567890',
    ]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openShareModal', $group->id)
        ->assertSet('sharingEnabled', true)
        ->call('toggleSharing')
        ->assertSet('sharingEnabled', false);

    $group->refresh();
    expect($group->is_shared)->toBeFalse();
});

test('token is generated when sharing is first enabled', function () {
    $group = WishlistGroup::factory()->create();

    expect($group->share_token)->toBeNull();

    $group->generateShareToken();

    expect($group->share_token)->not->toBeNull()
        ->and(strlen($group->share_token))->toBe(32);
});

test('token persists when toggling sharing off and on', function () {
    $user = User::factory()->create();
    $group = WishlistGroup::factory()->create();

    // Enable sharing
    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openShareModal', $group->id)
        ->call('toggleSharing');

    $group->refresh();
    $originalToken = $group->share_token;

    // Disable sharing
    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openShareModal', $group->id)
        ->call('toggleSharing');

    // Re-enable sharing
    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openShareModal', $group->id)
        ->call('toggleSharing');

    $group->refresh();
    expect($group->share_token)->toBe($originalToken);
});

test('can regenerate token', function () {
    $user = User::factory()->create();
    $group = WishlistGroup::factory()->create([
        'is_shared' => true,
        'share_token' => 'original-token-1234567890123456',
    ]);

    $originalToken = $group->share_token;

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openShareModal', $group->id)
        ->call('regenerateShareToken');

    $group->refresh();
    expect($group->share_token)->not->toBe($originalToken)
        ->and(strlen($group->share_token))->toBe(32);
});

test('public view accessible with valid token', function () {
    $group = WishlistGroup::factory()->create([
        'is_shared' => true,
        'share_token' => 'valid-token-123456789012345678',
    ]);

    Livewire::test(SharedView::class, ['token' => $group->share_token])
        ->assertStatus(200)
        ->assertSee($group->name);
});

test('public view returns 404 with invalid token', function () {
    $this->get('/delt/invalid-token')
        ->assertStatus(404);
});

test('public view returns 404 when sharing is disabled', function () {
    $group = WishlistGroup::factory()->create([
        'is_shared' => false,
        'share_token' => 'token-for-disabled-group-123456',
    ]);

    $this->get('/delt/'.$group->share_token)
        ->assertStatus(404);
});

test('public view shows correct items for group', function () {
    $group = WishlistGroup::factory()->create([
        'is_shared' => true,
        'share_token' => 'token-with-items-1234567890123456',
    ]);

    $item1 = WishlistItem::factory()->create([
        'group_id' => $group->id,
        'name' => 'First Item',
        'price' => 1000,
    ]);

    $item2 = WishlistItem::factory()->create([
        'group_id' => $group->id,
        'name' => 'Second Item',
        'price' => 2000,
    ]);

    // Item in different group
    $otherGroup = WishlistGroup::factory()->create();
    WishlistItem::factory()->create([
        'group_id' => $otherGroup->id,
        'name' => 'Other Item',
    ]);

    Livewire::test(SharedView::class, ['token' => $group->share_token])
        ->assertSee('First Item')
        ->assertSee('Second Item')
        ->assertDontSee('Other Item');
});

test('shared group shows is_shared indicator in wishlists', function () {
    $user = User::factory()->create();
    $group = WishlistGroup::factory()->create([
        'is_shared' => true,
        'share_token' => 'token-for-indicator-test-12345678',
    ]);

    $component = Livewire::actingAs($user)
        ->test(Index::class);

    $wishlists = $component->get('wishlists');
    $sharedGroup = $wishlists->first(fn ($w) => $w['is_group'] && $w['id'] === $group->id);

    expect($sharedGroup)->not->toBeNull()
        ->and($sharedGroup['is_shared'])->toBeTrue();
});
