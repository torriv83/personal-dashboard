<?php

declare(strict_types=1);

use App\Jobs\FetchWishlistImagesJob;
use App\Models\WishlistItem;
use App\Services\OpenGraphService;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Clear all wishlist items
    WishlistItem::query()->delete();
});

test('it processes items with url but no image', function () {
    // Create items with different scenarios
    $itemWithUrl = WishlistItem::factory()->create([
        'url' => 'https://example.com/product',
        'image_url' => null,
    ]);

    $itemWithImage = WishlistItem::factory()->create([
        'url' => 'https://example.com/product2',
        'image_url' => 'https://example.com/image.jpg',
    ]);

    $itemWithoutUrl = WishlistItem::factory()->create([
        'url' => null,
        'image_url' => null,
    ]);

    $itemWithEmptyUrl = WishlistItem::factory()->create([
        'url' => '',
        'image_url' => null,
    ]);

    // Mock the OpenGraphService
    $mockService = Mockery::mock(OpenGraphService::class);
    $mockService->shouldReceive('fetchImage')
        ->once()
        ->with('https://example.com/product')
        ->andReturn('https://example.com/fetched-image.jpg');

    app()->instance(OpenGraphService::class, $mockService);

    // Execute the job
    $job = new FetchWishlistImagesJob;
    $job->handle($mockService);

    // Assert the item was updated
    expect($itemWithUrl->fresh()->image_url)->toBe('https://example.com/fetched-image.jpg');

    // Assert other items were not touched
    expect($itemWithImage->fresh()->image_url)->toBe('https://example.com/image.jpg');
    expect($itemWithoutUrl->fresh()->image_url)->toBeNull();
    expect($itemWithEmptyUrl->fresh()->image_url)->toBeNull();
});

test('it handles multiple items', function () {
    // Create multiple items without images
    $item1 = WishlistItem::factory()->create([
        'url' => 'https://example.com/product1',
        'image_url' => null,
    ]);

    $item2 = WishlistItem::factory()->create([
        'url' => 'https://example.com/product2',
        'image_url' => null,
    ]);

    $item3 = WishlistItem::factory()->create([
        'url' => 'https://example.com/product3',
        'image_url' => null,
    ]);

    // Mock the service to return images for item1 and item2, but not item3
    $mockService = Mockery::mock(OpenGraphService::class);
    $mockService->shouldReceive('fetchImage')
        ->with('https://example.com/product1')
        ->andReturn('https://example.com/image1.jpg');

    $mockService->shouldReceive('fetchImage')
        ->with('https://example.com/product2')
        ->andReturn('https://example.com/image2.jpg');

    $mockService->shouldReceive('fetchImage')
        ->with('https://example.com/product3')
        ->andReturn(null);

    app()->instance(OpenGraphService::class, $mockService);

    // Execute the job
    $job = new FetchWishlistImagesJob;
    $job->handle($mockService);

    // Assert results
    expect($item1->fresh()->image_url)->toBe('https://example.com/image1.jpg');
    expect($item2->fresh()->image_url)->toBe('https://example.com/image2.jpg');
    expect($item3->fresh()->image_url)->toBeNull();
});

test('it continues processing after error', function () {
    // Create items
    $item1 = WishlistItem::factory()->create([
        'url' => 'https://example.com/product1',
        'image_url' => null,
    ]);

    $item2 = WishlistItem::factory()->create([
        'url' => 'https://example.com/product2',
        'image_url' => null,
    ]);

    // Mock the service to throw error for item1 but succeed for item2
    $mockService = Mockery::mock(OpenGraphService::class);
    $mockService->shouldReceive('fetchImage')
        ->with('https://example.com/product1')
        ->andThrow(new \Exception('Network error'));

    $mockService->shouldReceive('fetchImage')
        ->with('https://example.com/product2')
        ->andReturn('https://example.com/image2.jpg');

    app()->instance(OpenGraphService::class, $mockService);

    // Suppress log warnings for this test
    Log::shouldReceive('info')->andReturnNull();
    Log::shouldReceive('debug')->andReturnNull();
    Log::shouldReceive('warning')->andReturnNull();

    // Execute the job (should not throw)
    $job = new FetchWishlistImagesJob;
    $job->handle($mockService);

    // Assert item1 was not updated but item2 was
    expect($item1->fresh()->image_url)->toBeNull();
    expect($item2->fresh()->image_url)->toBe('https://example.com/image2.jpg');
});

test('it logs progress and results', function () {
    // Create items
    WishlistItem::factory()->create([
        'url' => 'https://example.com/product1',
        'image_url' => null,
    ]);

    WishlistItem::factory()->create([
        'url' => 'https://example.com/product2',
        'image_url' => null,
    ]);

    // Mock the service
    $mockService = Mockery::mock(OpenGraphService::class);
    $mockService->shouldReceive('fetchImage')->andReturn('https://example.com/image.jpg');

    app()->instance(OpenGraphService::class, $mockService);

    // Expect log calls
    Log::shouldReceive('info')
        ->once()
        ->with('FetchWishlistImagesJob: Starting image fetch process (mode: missing only)');

    Log::shouldReceive('info')
        ->once()
        ->with('FetchWishlistImagesJob: Found 2 items to process');

    Log::shouldReceive('info')
        ->once()
        ->with('FetchWishlistImagesJob: Completed', [
            'mode' => 'missing only',
            'total_items' => 2,
            'processed' => 2,
            'images_found' => 2,
            'images_not_found' => 0,
        ]);

    Log::shouldReceive('debug')->andReturnNull();

    // Execute the job
    $job = new FetchWishlistImagesJob;
    $job->handle($mockService);
});
