<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new API token
     */
    public function createToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'scopes' => 'array',
            'scopes.*' => 'in:read,write,admin'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $token = ApiToken::generateToken(
            $request->input('name', 'TecDoc API Token'),
            $request->input('scopes', ['read'])
        );

        return response()->json([
            'success' => true,
            'message' => 'API token created successfully',
            'data' => [
                'token' => $token->token,
                'name' => $token->name,
                'scopes' => $token->scopes,
                'expires_at' => $token->expires_at->toISOString(),
                'requests_limit' => $token->requests_limit
            ],
            'meta' => [
                'token_id' => $token->id,
                'created_at' => $token->created_at->toISOString()
            ]
        ], 201);
    }

    /**
     * Get token information
     */
    public function tokenInfo(Request $request): JsonResponse
    {
        $token = $request->apiToken;

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $token->name,
                'scopes' => $token->scopes,
                'expires_at' => $token->expires_at->toISOString(),
                'requests_used' => $token->requests_count,
                'requests_remaining' => $token->getRemainingRequests(),
                'requests_limit' => $token->requests_limit,
                'last_used_at' => $token->last_used_at?->toISOString(),
                'is_active' => $token->is_active
            ],
            'api_info' => [
                'version' => '2.0',
                'rate_limit' => [
                    'remaining' => $token->getRemainingRequests(),
                    'reset_at' => $token->last_used_at?->addHour()->toISOString()
                ]
            ]
        ]);
    }

    /**
     * Revoke API token
     */
    public function revokeToken(Request $request): JsonResponse
    {
        $token = $request->apiToken;
        $token->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'API token revoked successfully'
        ]);
    }

    /**
     * Refresh API token (extend expiration)
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $token = $request->apiToken;
        $token->update([
            'expires_at' => now()->addDays(30),
            'requests_count' => 0 // Reset usage counter
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API token refreshed successfully',
            'data' => [
                'expires_at' => $token->expires_at->toISOString(),
                'requests_remaining' => $token->getRemainingRequests()
            ]
        ]);
    }
}
