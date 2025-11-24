<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiToken;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$scopes): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Missing or invalid Authorization header',
                'error' => 'MISSING_TOKEN'
            ], 401);
        }

        $apiToken = ApiToken::where('token', $token)->first();

        if (!$apiToken || !$apiToken->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired API token',
                'error' => 'INVALID_TOKEN'
            ], 401);
        }

        // Check rate limiting
        if (!$apiToken->canMakeRequest()) {
            return response()->json([
                'success' => false,
                'message' => 'Rate limit exceeded',
                'error' => 'RATE_LIMIT_EXCEEDED',
                'meta' => [
                    'requests_limit' => $apiToken->requests_limit,
                    'reset_at' => $apiToken->last_used_at?->addHour()->toISOString()
                ]
            ], 429);
        }

        // Check scopes if provided
        if (!empty($scopes)) {
            $hasRequiredScope = false;
            foreach ($scopes as $scope) {
                if ($apiToken->hasScope($scope)) {
                    $hasRequiredScope = true;
                    break;
                }
            }

            if (!$hasRequiredScope) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'error' => 'INSUFFICIENT_SCOPE',
                    'required_scopes' => $scopes,
                    'token_scopes' => $apiToken->scopes
                ], 403);
            }
        }

        // Record usage
        $apiToken->recordUsage();

        // Add token to request for controllers
        $request->apiToken = $apiToken;

        // Add rate limit headers
        $response = $next($request);
        
        $response->headers->set('X-RateLimit-Limit', $apiToken->requests_limit);
        $response->headers->set('X-RateLimit-Remaining', $apiToken->getRemainingRequests());
        $response->headers->set('X-RateLimit-Reset', $apiToken->last_used_at?->addHour()->timestamp);

        return $response;
    }

    /**
     * Extract token from Authorization header
     */
    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}
