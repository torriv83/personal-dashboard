<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('app.api_key');

        if (empty($apiKey)) {
            return response()->json(['error' => 'API not configured'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $providedKey = $request->bearerToken();

        if (! $providedKey || ! hash_equals($apiKey, $providedKey)) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
