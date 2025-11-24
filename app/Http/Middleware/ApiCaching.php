<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiCaching
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$parameters): Response
    {
        $response = $next($request);

        // Only apply caching to successful GET requests
        if ($request->isMethod('GET') && $response->isSuccessful()) {
            $this->applyCachingHeaders($response, $request, $parameters);
        }

        return $response;
    }

    /**
     * Apply caching headers based on endpoint type
     */
    private function applyCachingHeaders(Response $response, Request $request, array $parameters): void
    {
        $maxAge = $this->getMaxAge($request, $parameters);
        $etag = $this->generateETag($response);
        
        // Set cache headers
        $response->headers->set('Cache-Control', "public, max-age={$maxAge}, must-revalidate");
        $response->headers->set('ETag', $etag);
        $response->headers->set('Last-Modified', now()->toRfc7231String());
        $response->headers->set('Vary', 'Accept, Authorization, Accept-Encoding');
        
        // Add custom headers for API clients
        $response->headers->set('X-Cache-TTL', $maxAge);
        $response->headers->set('X-Cache-Strategy', $this->getCacheStrategy($request));
        
        // Handle conditional requests
        if ($this->isNotModified($request, $etag)) {
            $response->setStatusCode(304);
            $response->setContent('');
        }
    }

    /**
     * Get max age based on endpoint
     */
    private function getMaxAge(Request $request, array $parameters): int
    {
        // Custom max age from middleware parameter
        if (!empty($parameters) && is_numeric($parameters[0])) {
            return (int) $parameters[0];
        }

        $path = $request->path();
        
        // Different cache times for different endpoints
        return match (true) {
            str_contains($path, 'vehicle-types') => 7200,      // 2 hours - rarely changes
            str_contains($path, 'part-categories') => 7200,    // 2 hours - rarely changes
            str_contains($path, 'manufacturers') => 3600,      // 1 hour - occasionally changes
            str_contains($path, 'models') => 3600,             // 1 hour - occasionally changes
            str_contains($path, 'versions') => 3600,           // 1 hour - occasionally changes
            str_contains($path, 'articles') && str_contains($path, 'details') => 1800, // 30 min - article details
            str_contains($path, 'search-articles') => 900,     // 15 min - search results
            str_contains($path, 'auth/token-info') => 300,     // 5 min - token info
            default => 1800 // 30 minutes default
        };
    }

    /**
     * Generate ETag for response
     */
    private function generateETag(Response $response): string
    {
        $content = $response->getContent();
        return '"' . md5($content) . '"';
    }

    /**
     * Get cache strategy description
     */
    private function getCacheStrategy(Request $request): string
    {
        $path = $request->path();
        
        return match (true) {
            str_contains($path, 'vehicle-types') => 'static-long',
            str_contains($path, 'part-categories') => 'static-long',
            str_contains($path, 'manufacturers') => 'semi-static',
            str_contains($path, 'search-articles') => 'dynamic-short',
            default => 'standard'
        };
    }

    /**
     * Check if content is not modified
     */
    private function isNotModified(Request $request, string $etag): bool
    {
        $ifNoneMatch = $request->header('If-None-Match');
        
        if ($ifNoneMatch && $ifNoneMatch === $etag) {
            return true;
        }

        return false;
    }
}
