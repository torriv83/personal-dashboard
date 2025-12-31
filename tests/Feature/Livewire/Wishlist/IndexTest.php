<?php

declare(strict_types=1);

use App\Livewire\Wishlist\Index;
use App\Models\User;
use App\Models\WishlistGroup;
use App\Models\WishlistItem;
use Livewire\Livewire;

test('wishlist page loads for authenticated user', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertStatus(200);
});

test('can create a standalone item', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openItemModal')
        ->set('itemNavn', 'Test Item')
        ->set('itemPris', 1000)
        ->set('itemAntall', 1)
        ->call('saveItem');

    expect(WishlistItem::where('name', 'Test Item')->exists())->toBeTrue();
});

test('can create a group', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('openGroupModal')
        ->set('groupNavn', 'Test Group')
        ->call('saveGroup');

    expect(WishlistGroup::where('name', 'Test Group')->exists())->toBeTrue();
});

test('can delete an item', function () {
    $user = User::factory()->create();
    $item = WishlistItem::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('deleteItem', $item->id);

    expect(WishlistItem::find($item->id))->toBeNull();
});

test('can delete a group', function () {
    $user = User::factory()->create();
    $group = WishlistGroup::factory()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('deleteGroup', $group->id);

    expect(WishlistGroup::find($group->id))->toBeNull();
});

test('displays standalone items', function () {
    $user = User::factory()->create();
    $item = WishlistItem::factory()->standalone()->create(['name' => 'Visible Item']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Visible Item');
});

test('displays groups', function () {
    $user = User::factory()->create();
    WishlistGroup::factory()->create(['name' => 'Visible Group']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Visible Group');
});
