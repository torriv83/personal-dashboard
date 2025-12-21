<?php

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
    $fakeImageData = file_get_contents(__DIR__.'/../fixtures/test-image.jpg');

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
    Storage::disk('public')->assertExists('wishlist-images/'.$filename);
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

    $fakeImageData = file_get_contents(__DIR__.'/../fixtures/test-image.jpg');

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

    $fakeImageData = file_get_contents(__DIR__.'/../fixtures/test-image.jpg');

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

    $fakeImageData = file_get_contents(__DIR__.'/../fixtures/test-image.jpg');

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

    $fakeImageData = file_get_contents(__DIR__.'/../fixtures/test-image.jpg');

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

    $fakeImageData = file_get_contents(__DIR__.'/../fixtures/test-image.jpg');

    Http::fake([
        'https://example.com' => Http::response($html, 200),
        'https://example.com/image' => Http::response($fakeImageData, 200, [
            'Content-Type' => $mimeType,
        ]),
    ]);

    $result = $this->service->fetchImage('https://example.com');

    expect($result)->toStartWith('/storage/wishlist-images/')
        ->and($result)->toEndWith('.'.$extension);
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

    $fakeImageData = file_get_contents(__DIR__.'/../fixtures/test-image.jpg');

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
