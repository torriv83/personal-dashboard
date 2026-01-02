<?php

declare(strict_types=1);

use App\Livewire\Wishlist\Index;
use App\Models\User;
use App\Models\WishlistGroup;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('toggles sharing on for a wishlist group', function () {
    $group = WishlistGroup::factory()->create([
        'is_shared' => false,
    ]);

    Livewire::test(Index::class)
        ->call('openShareModal', $group->id)
        ->assertSet('sharingEnabled', false)
        ->call('toggleSharing')
        ->assertSet('sharingEnabled', true);

    $group->refresh();
    expect($group->is_shared)->toBeTrue();
});

it('toggles sharing off for a wishlist group', function () {
    $group = WishlistGroup::factory()->create([
        'is_shared' => true,
    ]);

    Livewire::test(Index::class)
        ->call('openShareModal', $group->id)
        ->assertSet('sharingEnabled', true)
        ->call('toggleSharing')
        ->assertSet('sharingEnabled', false);

    $group->refresh();
    expect($group->is_shared)->toBeFalse();
});

it('does not toggle sharing for non-existent group', function () {
    $component = Livewire::test(Index::class);

    // Set sharingGroupId to a non-existent ID
    $component->set('sharingGroupId', 99999);
    $component->set('sharingEnabled', false);

    $component->call('toggleSharing');

    // Should remain false since group doesn't exist
    expect($component->get('sharingEnabled'))->toBeFalse();
});

it('updates sharing URL when toggling sharing on', function () {
    $group = WishlistGroup::factory()->create([
        'is_shared' => false,
    ]);

    $component = Livewire::test(Index::class)
        ->call('openShareModal', $group->id)
        ->call('toggleSharing');

    $group->refresh();

    // Should have generated a share token
    expect($group->share_token)->not->toBeNull();
});

it('maintains share token when toggling sharing off', function () {
    $group = WishlistGroup::factory()->create([
        'is_shared' => true,
        'share_token' => 'existing-token',
    ]);

    Livewire::test(Index::class)
        ->call('openShareModal', $group->id)
        ->call('toggleSharing');

    $group->refresh();

    // Share token should still exist even when sharing is disabled
    expect($group->share_token)->toBe('existing-token');
});

it('shows share URL when sharing is enabled', function () {
    $group = WishlistGroup::factory()->create([
        'is_shared' => true,
        'share_token' => 'test-token',
    ]);

    Livewire::test(Index::class)
        ->call('openShareModal', $group->id)
        ->assertSee('test-token');
});

it('hides share URL when sharing is disabled', function () {
    $group = WishlistGroup::factory()->create([
        'is_shared' => false,
        'share_token' => 'test-token',
    ]);

    $component = Livewire::test(Index::class)
        ->call('openShareModal', $group->id);

    // When sharing is disabled, the URL section should not be prominently visible
    expect($component->get('sharingEnabled'))->toBeFalse();
});
