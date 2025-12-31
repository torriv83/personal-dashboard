<?php

declare(strict_types=1);

use App\Livewire\Wishlist\Index;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\OpenGraphService;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can save wishlist item with image url', function () {
    $mockService = Mockery::mock(OpenGraphService::class);
    $mockService->shouldReceive('downloadAndStoreImage')
        ->with('https://example.com/image.jpg')
        ->andReturn('/storage/wishlist-images/test-image.jpg');

    $this->app->instance(OpenGraphService::class, $mockService);

    Livewire::test(Index::class)
        ->set('itemNavn', 'Test Item')
        ->set('itemUrl', 'https://example.com')
        ->set('itemImageUrl', 'https://example.com/image.jpg')
        ->set('itemPris', 1000)
        ->set('itemAntall', 1)
        ->call('saveItem');

    $item = WishlistItem::where('name', 'Test Item')->first();

    expect($item)->not->toBeNull()
        ->and($item->name)->toBe('Test Item')
        ->and($item->image_url)->toBe('/storage/wishlist-images/test-image.jpg');
});

test('can fetch image from url', function () {
    $mockService = Mockery::mock(OpenGraphService::class);
    $mockService->shouldReceive('fetchImage')
        ->with('https://example.com')
        ->andReturn('https://example.com/og-image.jpg');

    $this->app->instance(OpenGraphService::class, $mockService);

    Livewire::test(Index::class)
        ->set('itemUrl', 'https://example.com')
        ->call('fetchImageFromUrl')
        ->assertSet('itemImageUrl', 'https://example.com/og-image.jpg')
        ->assertDispatched('toast', type: 'success', message: 'Bilde hentet');
});

test('shows error when fetching image without url', function () {
    Livewire::test(Index::class)
        ->set('itemUrl', '')
        ->call('fetchImageFromUrl')
        ->assertDispatched('toast', type: 'error', message: 'Legg inn en URL først');
});

test('shows error when no image found on url', function () {
    $mockService = Mockery::mock(OpenGraphService::class);
    $mockService->shouldReceive('fetchImage')
        ->with('https://example.com')
        ->andReturn(null);

    $this->app->instance(OpenGraphService::class, $mockService);

    Livewire::test(Index::class)
        ->set('itemUrl', 'https://example.com')
        ->call('fetchImageFromUrl')
        ->assertDispatched('toast', type: 'error', message: 'Fant ikke bilde på denne siden');
});

test('auto-fetches image when creating item with url but no image', function () {
    $mockService = Mockery::mock(OpenGraphService::class);
    $mockService->shouldReceive('fetchImage')
        ->once()
        ->with('https://example.com/product')
        ->andReturn('https://example.com/og-image.jpg');

    $this->app->instance(OpenGraphService::class, $mockService);

    Livewire::test(Index::class)
        ->set('itemNavn', 'Auto Fetch Test')
        ->set('itemUrl', 'https://example.com/product')
        ->set('itemPris', 1000)
        ->set('itemAntall', 1)
        ->call('saveItem');

    $item = WishlistItem::where('name', 'Auto Fetch Test')->first();

    expect($item)->not->toBeNull()
        ->and($item->image_url)->toBe('https://example.com/og-image.jpg');
});

test('does not auto-fetch image when editing existing item', function () {
    $mockService = Mockery::mock(OpenGraphService::class);
    $mockService->shouldNotReceive('fetchImage');

    $this->app->instance(OpenGraphService::class, $mockService);

    $item = WishlistItem::factory()->create([
        'name' => 'Existing Item',
        'url' => 'https://example.com',
        'image_url' => null,
        'price' => 1000,
        'quantity' => 1,
    ]);

    Livewire::test(Index::class)
        ->call('openItemModal', $item->id)
        ->set('itemNavn', 'Updated Item')
        ->call('saveItem');

    $item->refresh();

    expect($item->name)->toBe('Updated Item')
        ->and($item->image_url)->toBeNull();
});

test('resets image url when closing modal', function () {
    Livewire::test(Index::class)
        ->set('itemImageUrl', 'https://example.com/image.jpg')
        ->call('closeItemModal')
        ->assertSet('itemImageUrl', '');
});

test('displays image thumbnail in modal when image url is set', function () {
    $item = WishlistItem::factory()->create([
        'name' => 'Test Item',
        'image_url' => 'https://example.com/image.jpg',
        'price' => 1000,
        'quantity' => 1,
    ]);

    Livewire::test(Index::class)
        ->call('openItemModal', $item->id)
        ->assertSet('itemImageUrl', 'https://example.com/image.jpg')
        ->assertSee('https://example.com/image.jpg');
});

test('preserves local storage paths without re-downloading', function () {
    $mockService = Mockery::mock(OpenGraphService::class);
    $mockService->shouldNotReceive('downloadAndStoreImage');
    $mockService->shouldNotReceive('fetchImage');

    $this->app->instance(OpenGraphService::class, $mockService);

    Livewire::test(Index::class)
        ->set('itemNavn', 'Local Image Test')
        ->set('itemUrl', 'https://example.com')
        ->set('itemImageUrl', '/storage/wishlist-images/existing-image.jpg')
        ->set('itemPris', 500)
        ->set('itemAntall', 1)
        ->call('saveItem');

    $item = WishlistItem::where('name', 'Local Image Test')->first();

    expect($item)->not->toBeNull()
        ->and($item->image_url)->toBe('/storage/wishlist-images/existing-image.jpg');
});

test('can paste image as base64 and store it locally', function () {
    Illuminate\Support\Facades\Storage::fake('public');

    // Minimal valid PNG base64 (1x1 transparent pixel)
    $base64Png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    Livewire::test(Index::class)
        ->call('handlePastedImage', $base64Png)
        ->assertDispatched('toast', type: 'success', message: 'Bilde limt inn');

    // Verify the image was stored
    $component = Livewire::test(Index::class);
    $component->call('handlePastedImage', $base64Png);

    $imageUrl = $component->get('itemImageUrl');
    expect($imageUrl)->toStartWith('/storage/wishlist-images/')
        ->and($imageUrl)->toEndWith('.png');
});

test('shows error when pasting invalid image format', function () {
    Livewire::test(Index::class)
        ->call('handlePastedImage', 'data:text/plain;base64,SGVsbG8gV29ybGQ=')
        ->assertDispatched('toast', type: 'error', message: 'Ugyldig bildeformat');
});

test('shows error when pasting corrupted base64 data', function () {
    Livewire::test(Index::class)
        ->call('handlePastedImage', 'not-valid-base64-at-all')
        ->assertDispatched('toast', type: 'error', message: 'Ugyldig bildeformat');
});
