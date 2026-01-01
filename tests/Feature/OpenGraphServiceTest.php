<?php

declare(strict_types=1);

use App\Services\OpenGraphService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = new OpenGraphService;
    Storage::fake('public');
});

test('fetches og:image from HTML and stores it locally', function () {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <meta property="og:image" content="https://example.com/image.jpg" />
    </head>
    <body>Test</body>
    </html>
    HTML;

    // Create a fake image data
    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');

    Http::fake([
        'https://example.com' => Http::response($html, 200),
        'https://example.com/image.jpg' => Http::response($fakeImageData, 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toStartWith('/storage/wishlist-images/')
        ->and($result)->toEndWith('.jpg');

    // Verify file was stored
    $filename = basename($result);
    Storage::disk('public')->assertExists('wishlist-images/' . $filename);
});

test('falls back to twitter:image when og:image is not found', function () {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <meta name="twitter:image" content="https://example.com/twitter-image.jpg" />
    </head>
    <body>Test</body>
    </html>
    HTML;

    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');

    Http::fake([
        'https://example.com' => Http::response($html, 200),
        'https://example.com/twitter-image.jpg' => Http::response($fakeImageData, 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toStartWith('/storage/wishlist-images/')
        ->and($result)->toEndWith('.jpg');
});

test('converts relative image URLs to absolute', function () {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <meta property="og:image" content="/images/og.jpg" />
    </head>
    <body>Test</body>
    </html>
    HTML;

    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');

    Http::fake([
        'https://example.com/page' => Http::response($html, 200),
        'https://example.com/images/og.jpg' => Http::response($fakeImageData, 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $result = $this->service->fetchImage('https://example.com/page');

    expect($result)->toStartWith('/storage/wishlist-images/')
        ->and($result)->toEndWith('.jpg');
});

test('handles protocol-relative URLs', function () {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <meta property="og:image" content="//cdn.example.com/image.jpg" />
    </head>
    <body>Test</body>
    </html>
    HTML;

    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');

    Http::fake([
        'https://example.com' => Http::response($html, 200),
        'https://cdn.example.com/image.jpg' => Http::response($fakeImageData, 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toStartWith('/storage/wishlist-images/')
        ->and($result)->toEndWith('.jpg');
});

test('returns null for invalid URLs', function () {
    $result = $this->service->fetchImage('not-a-valid-url');

    expect($result)->toBeNull();
});

test('returns null when request fails', function () {
    Http::fake([
        'https://example.com' => Http::response(null, 404),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toBeNull();
});

test('returns null when no og:image or twitter:image is found', function () {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <title>Test Page</title>
    </head>
    <body>Test</body>
    </html>
    HTML;

    Http::fake([
        'https://example.com' => Http::response($html, 200),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toBeNull();
});

test('handles meta tags with content before property', function () {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <meta content="https://example.com/reversed.jpg" property="og:image" />
    </head>
    <body>Test</body>
    </html>
    HTML;

    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');

    Http::fake([
        'https://example.com' => Http::response($html, 200),
        'https://example.com/reversed.jpg' => Http::response($fakeImageData, 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toStartWith('/storage/wishlist-images/')
        ->and($result)->toEndWith('.jpg');
});

test('returns null when image URL is invalid after extraction', function () {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <meta property="og:image" content="not-a-valid-url" />
    </head>
    <body>Test</body>
    </html>
    HTML;

    Http::fake([
        'https://example.com' => Http::response($html, 200),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toBeNull();
});

test('handles network exceptions gracefully', function () {
    Http::fake(function () {
        throw new \Exception('Network error');
    });

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toBeNull();
});

test('returns null when image download fails', function () {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <meta property="og:image" content="https://example.com/image.jpg" />
    </head>
    <body>Test</body>
    </html>
    HTML;

    Http::fake([
        'https://example.com' => Http::response($html, 200),
        'https://example.com/image.jpg' => Http::response(null, 404),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toBeNull();
});

test('returns null when image is too large', function () {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <meta property="og:image" content="https://example.com/large-image.jpg" />
    </head>
    <body>Test</body>
    </html>
    HTML;

    // Create a fake image that's larger than 5MB
    $largeImageData = str_repeat('x', 6 * 1024 * 1024); // 6MB

    Http::fake([
        'https://example.com' => Http::response($html, 200),
        'https://example.com/large-image.jpg' => Http::response($largeImageData, 200, [
            'Content-Type' => 'image/jpeg',
        ]),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toBeNull();
});

test('handles different image formats', function ($mimeType, $extension) {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <meta property="og:image" content="https://example.com/image" />
    </head>
    <body>Test</body>
    </html>
    HTML;

    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');

    Http::fake([
        'https://example.com' => Http::response($html, 200),
        'https://example.com/image' => Http::response($fakeImageData, 200, [
            'Content-Type' => $mimeType,
        ]),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toStartWith('/storage/wishlist-images/')
        ->and($result)->toEndWith('.' . $extension);
})->with([
    ['image/jpeg', 'jpg'],
    ['image/png', 'png'],
    ['image/webp', 'webp'],
    ['image/gif', 'gif'],
]);

test('returns null for unsupported image formats', function () {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <meta property="og:image" content="https://example.com/image.bmp" />
    </head>
    <body>Test</body>
    </html>
    HTML;

    Http::fake([
        'https://example.com' => Http::response($html, 200),
        'https://example.com/image.bmp' => Http::response('fake-data', 200, [
            'Content-Type' => 'image/bmp',
        ]),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toBeNull();
});

test('detects MIME type from image content when header is missing', function () {
    $html = <<<'HTML'
    <!DOCTYPE html>
    <html>
    <head>
        <meta property="og:image" content="https://example.com/image.jpg" />
    </head>
    <body>Test</body>
    </html>
    HTML;

    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');

    Http::fake([
        'https://example.com' => Http::response($html, 200),
        'https://example.com/image.jpg' => Http::response($fakeImageData, 200, [
            'Content-Type' => 'application/octet-stream', // Generic type
        ]),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toStartWith('/storage/wishlist-images/')
        ->and($result)->toEndWith('.jpg');
});

// Tests for storeBase64Image method

test('stores valid JPEG base64 image', function () {
    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');
    $base64Data = 'data:image/jpeg;base64,' . base64_encode($fakeImageData);

    $result = $this->service->storeBase64Image($base64Data);

    expect($result)->toHaveKey('success')
        ->and($result['success'])->toBeTrue()
        ->and($result)->toHaveKey('path')
        ->and($result['path'])->toStartWith('/storage/wishlist-images/')
        ->and($result['path'])->toEndWith('.jpg');

    // Verify file was stored
    $filename = basename($result['path']);
    Storage::disk('public')->assertExists('wishlist-images/' . $filename);
});

test('stores valid PNG base64 image', function () {
    // Create a minimal PNG (1x1 pixel)
    $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $base64Data = 'data:image/png;base64,' . base64_encode($pngData);

    $result = $this->service->storeBase64Image($base64Data);

    expect($result)->toHaveKey('success')
        ->and($result['success'])->toBeTrue()
        ->and($result)->toHaveKey('path')
        ->and($result['path'])->toStartWith('/storage/wishlist-images/')
        ->and($result['path'])->toEndWith('.png');

    // Verify file was stored
    $filename = basename($result['path']);
    Storage::disk('public')->assertExists('wishlist-images/' . $filename);
});

test('stores valid GIF base64 image', function () {
    // Create a minimal GIF (1x1 pixel)
    $gifData = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    $base64Data = 'data:image/gif;base64,' . base64_encode($gifData);

    $result = $this->service->storeBase64Image($base64Data);

    expect($result)->toHaveKey('success')
        ->and($result['success'])->toBeTrue()
        ->and($result)->toHaveKey('path')
        ->and($result['path'])->toStartWith('/storage/wishlist-images/')
        ->and($result['path'])->toEndWith('.gif');

    // Verify file was stored
    $filename = basename($result['path']);
    Storage::disk('public')->assertExists('wishlist-images/' . $filename);
});

test('handles jpg extension for jpeg mime type', function () {
    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');
    $base64Data = 'data:image/jpg;base64,' . base64_encode($fakeImageData);

    $result = $this->service->storeBase64Image($base64Data);

    expect($result)->toHaveKey('success')
        ->and($result['success'])->toBeTrue()
        ->and($result['path'])->toEndWith('.jpg');
});

test('rejects invalid base64 format', function () {
    $result = $this->service->storeBase64Image('not-a-base64-string');

    expect($result)->toHaveKey('success')
        ->and($result['success'])->toBeFalse()
        ->and($result)->toHaveKey('error')
        ->and($result['error'])->toBe('Invalid image format');
});

test('rejects unsupported image format', function () {
    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');
    $base64Data = 'data:image/bmp;base64,' . base64_encode($fakeImageData);

    $result = $this->service->storeBase64Image($base64Data);

    expect($result)->toHaveKey('success')
        ->and($result['success'])->toBeFalse()
        ->and($result)->toHaveKey('error')
        ->and($result['error'])->toBe('Invalid image format');
});

test('rejects base64 without data URL scheme', function () {
    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');
    $base64String = base64_encode($fakeImageData);

    $result = $this->service->storeBase64Image($base64String);

    expect($result)->toHaveKey('success')
        ->and($result['success'])->toBeFalse()
        ->and($result)->toHaveKey('error')
        ->and($result['error'])->toBe('Invalid image format');
});

test('rejects oversized base64 image', function () {
    // Create a fake image larger than 5MB (6MB)
    $largeImageData = str_repeat('x', 6 * 1024 * 1024);
    $base64Data = 'data:image/jpeg;base64,' . base64_encode($largeImageData);

    $result = $this->service->storeBase64Image($base64Data);

    expect($result)->toHaveKey('success')
        ->and($result['success'])->toBeFalse()
        ->and($result)->toHaveKey('error')
        ->and($result['error'])->toBe('Image too large (max 5MB)');
});

test('handles corrupted base64 data', function () {
    $base64Data = 'data:image/jpeg;base64,!!!invalid-base64-data!!!';

    $result = $this->service->storeBase64Image($base64Data);

    expect($result)->toHaveKey('success')
        ->and($result['success'])->toBeFalse()
        ->and($result)->toHaveKey('error')
        ->and($result['error'])->toBe('Could not decode image');
});

test('generates unique file paths for base64 images', function () {
    $fakeImageData = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');
    $base64Data = 'data:image/jpeg;base64,' . base64_encode($fakeImageData);

    $result1 = $this->service->storeBase64Image($base64Data);
    $result2 = $this->service->storeBase64Image($base64Data);

    expect($result1['success'])->toBeTrue()
        ->and($result2['success'])->toBeTrue()
        ->and($result1['path'])->not->toBe($result2['path']);

    // Verify both files were stored
    $filename1 = basename($result1['path']);
    $filename2 = basename($result2['path']);
    Storage::disk('public')->assertExists('wishlist-images/' . $filename1);
    Storage::disk('public')->assertExists('wishlist-images/' . $filename2);
});

test('stores webp base64 image', function () {
    // Create minimal WebP header (this is a valid 1x1 WebP)
    $webpData = base64_decode('UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAwA0JaQAA3AA/vuUAAA=');
    $base64Data = 'data:image/webp;base64,' . base64_encode($webpData);

    $result = $this->service->storeBase64Image($base64Data);

    expect($result)->toHaveKey('success')
        ->and($result['success'])->toBeTrue()
        ->and($result)->toHaveKey('path')
        ->and($result['path'])->toStartWith('/storage/wishlist-images/')
        ->and($result['path'])->toEndWith('.webp');

    // Verify file was stored
    $filename = basename($result['path']);
    Storage::disk('public')->assertExists('wishlist-images/' . $filename);
});
