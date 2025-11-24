<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    /**
     * Standard API response format
     */
    protected function apiResponse(
        $data = null, 
        string $message = 'Success', 
        bool $success = true, 
        int $status = 200,
        array $meta = [],
        array $performance = []
    ): JsonResponse {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        if (!empty($performance)) {
            $response['performance'] = $performance;
        }

        // Add API info
        $response['api_info'] = [
            'version' => '2.0',
            'timestamp' => now()->toISOString()
        ];

        return response()->json($response, $status);
    }

    /**
     * Paginated response
     */
    protected function paginatedResponse($paginator, array $performance = []): JsonResponse
    {
        return $this->apiResponse(
            $paginator->items(),
            'Data retrieved successfully',
            true,
            200,
            [
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'has_more' => $paginator->hasMorePages()
                ]
            ],
            $performance
        );
    }

    /**
     * Error response
     */
    protected function errorResponse(
        string $message, 
        int $status = 400, 
        string $errorCode = null,
        array $errors = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        $response['api_info'] = [
            'version' => '2.0',
            'timestamp' => now()->toISOString()
        ];

        return response()->json($response, $status);
    }

    /**
     * Get data mode from request (light or full)
     */
    protected function getDataMode(Request $request): string
    {
        return $request->query('mode', 'light');
    }

    /**
     * Check if full data mode is requested
     */
    protected function isFullMode(Request $request): bool
    {
        return $this->getDataMode($request) === 'full';
    }

    /**
     * Add caching headers
     */
    protected function addCacheHeaders($response, int $maxAge = 3600): void
    {
        $response->headers->set('Cache-Control', "public, max-age={$maxAge}");
        $response->headers->set('ETag', md5($response->getContent()));
        $response->headers->set('Last-Modified', now()->toRfc7231String());
    }
}
