<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpenGraphService
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

    private const STORAGE_PATH = 'wishlist-images';

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /**
     * Fetch Open Graph image from a URL and store it locally.
     */
    public function fetchImage(string $url): ?string
    {
        try {
            // Validate URL format
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                Log::debug('OpenGraphService: Invalid URL format', ['url' => $url]);

                return null;
            }

            // Fetch the HTML with timeout and user agent
            $response = Http::timeout(5)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->get($url);

            if (! $response->successful()) {
                Log::debug('OpenGraphService: Request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $html = $response->body();

            // Try to find og:image first
            $imageUrl = $this->extractMetaTag($html, 'og:image');

            // Fallback to twitter:image
            if (! $imageUrl) {
                $imageUrl = $this->extractMetaTag($html, 'twitter:image');
            }

            if (! $imageUrl) {
                return null;
            }

            // Convert relative URLs to absolute
            $imageUrl = $this->makeAbsoluteUrl($imageUrl, $url);

            // Validate that the result is a valid URL
            if (! filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                Log::debug('OpenGraphService: Invalid image URL', ['imageUrl' => $imageUrl]);

                return null;
            }

            // Download and store the image
            return $this->downloadAndStoreImage($imageUrl);
        } catch (\Exception $e) {
            Log::warning('OpenGraphService: Error fetching image', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Download an image and store it locally.
     */
    public function downloadAndStoreImage(string $imageUrl): ?string
    {
        try {
            // Download the image
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->get($imageUrl);

            if (! $response->successful()) {
                Log::debug('OpenGraphService: Image download failed', [
                    'url' => $imageUrl,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $imageData = $response->body();

            // Check file size
            if (strlen($imageData) > self::MAX_FILE_SIZE) {
                Log::debug('OpenGraphService: Image too large', [
                    'url' => $imageUrl,
                    'size' => strlen($imageData),
                ]);

                return null;
            }

            // Detect MIME type from content-type header or file content
            $mimeType = $response->header('Content-Type');
            if ($mimeType && str_contains($mimeType, ';')) {
                $mimeType = explode(';', $mimeType)[0];
            }

            // Validate MIME type
            if (! $mimeType || ! isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
                // Try to detect from image data
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detectedMimeType = finfo_buffer($finfo, $imageData);
                finfo_close($finfo);

                if ($detectedMimeType === false || ! isset(self::ALLOWED_MIME_TYPES[$detectedMimeType])) {
                    Log::debug('OpenGraphService: Unsupported image type', [
                        'url' => $imageUrl,
                        'mime_type' => $mimeType ?: 'unknown',
                        'detected_mime_type' => $detectedMimeType ?: 'unknown',
                    ]);

                    return null;
                }

                $mimeType = $detectedMimeType;
            }

            $extension = self::ALLOWED_MIME_TYPES[$mimeType];

            // Generate unique filename based on URL hash
            $filename = Str::slug(md5($imageUrl)) . '-' . time() . '.' . $extension;
            $filePath = self::STORAGE_PATH . '/' . $filename;

            // Store the image
            Storage::disk('public')->put($filePath, $imageData);

            // Return public URL path
            return '/storage/' . $filePath;
        } catch (\Exception $e) {
            Log::warning('OpenGraphService: Error downloading image', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Store a base64-encoded image.
     */
    public function storeBase64Image(string $base64Data): array
    {
        try {
            // Extract mime type and data from base64 string
            if (! preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $base64Data, $matches)) {
                return [
                    'success' => false,
                    'error' => 'Invalid image format',
                ];
            }

            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $base64String = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);

            if ($base64String === null) {
                return [
                    'success' => false,
                    'error' => 'Could not decode image',
                ];
            }

            $imageData = base64_decode($base64String, true);

            if ($imageData === false) {
                return [
                    'success' => false,
                    'error' => 'Could not decode image',
                ];
            }

            // Check file size
            if (strlen($imageData) > self::MAX_FILE_SIZE) {
                Log::debug('OpenGraphService: Base64 image too large', [
                    'size' => strlen($imageData),
                ]);

                return [
                    'success' => false,
                    'error' => 'Image too large (max 5MB)',
                ];
            }

            // Generate unique filename
            $filename = md5(uniqid()) . '-' . time() . '.' . $extension;
            $filePath = self::STORAGE_PATH . '/' . $filename;

            // Store the image
            Storage::disk('public')->put($filePath, $imageData);

            // Return public URL path
            return [
                'success' => true,
                'path' => '/storage/' . $filePath,
            ];
        } catch (\Exception $e) {
            Log::warning('OpenGraphService: Error storing base64 image', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Could not store image',
            ];
        }
    }

    /**
     * Extract meta tag content from HTML.
     */
    private function extractMetaTag(string $html, string $property): ?string
    {
        // Try property attribute first (for og:image)
        $pattern = '/<meta[^>]+property=["\']' . preg_quote($property, '/') . '["\'][^>]+content=["\'](.*?)["\']/i';
        if (preg_match($pattern, $html, $matches)) {
            return trim($matches[1]);
        }

        // Try reversed order (content before property)
        $pattern = '/<meta[^>]+content=["\'](.*?)["\'][^>]+property=["\']' . preg_quote($property, '/') . '["\']/i';
        if (preg_match($pattern, $html, $matches)) {
            return trim($matches[1]);
        }

        // Try name attribute (for twitter:image)
        $pattern = '/<meta[^>]+name=["\']' . preg_quote($property, '/') . '["\'][^>]+content=["\'](.*?)["\']/i';
        if (preg_match($pattern, $html, $matches)) {
            return trim($matches[1]);
        }

        // Try reversed order (content before name)
        $pattern = '/<meta[^>]+content=["\'](.*?)["\'][^>]+name=["\']' . preg_quote($property, '/') . '["\']/i';
        if (preg_match($pattern, $html, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Convert relative URL to absolute URL.
     */
    private function makeAbsoluteUrl(string $imageUrl, string $baseUrl): string
    {
        // Already absolute
        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            return $imageUrl;
        }

        // Protocol-relative URL (//example.com/image.jpg)
        if (str_starts_with($imageUrl, '//')) {
            $parsedBase = parse_url($baseUrl);

            return ($parsedBase['scheme'] ?? 'https') . ':' . $imageUrl;
        }

        $parsedBase = parse_url($baseUrl);
        $scheme = $parsedBase['scheme'] ?? 'https';
        $host = $parsedBase['host'] ?? '';

        // Root-relative URL (/image.jpg)
        if (str_starts_with($imageUrl, '/')) {
            return "{$scheme}://{$host}" . $imageUrl;
        }

        // Relative URL (image.jpg or ../image.jpg)
        $basePath = $parsedBase['path'] ?? '/';
        $basePath = rtrim(dirname($basePath), '/');

        return "{$scheme}://{$host}{$basePath}/" . ltrim($imageUrl, '/');
    }
}
