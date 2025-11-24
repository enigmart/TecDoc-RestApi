<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApiToken extends Model
{
    protected $fillable = [
        'token',
        'name',
        'scopes',
        'expires_at',
        'last_used_at',
        'requests_count',
        'requests_limit',
        'is_active'
    ];

    protected $casts = [
        'scopes' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    /**
     * Generate a new API token
     */
    public static function generateToken($name = 'API Token', $scopes = ['read'])
    {
        return self::create([
            'token' => 'tecdoc_' . Str::random(60),
            'name' => $name,
            'scopes' => $scopes,
            'expires_at' => Carbon::now()->addDays(30),
            'requests_count' => 0,
            'requests_limit' => 1000, // per hour
            'is_active' => true
        ]);
    }

    /**
     * Check if token is valid
     */
    public function isValid()
    {
        return $this->is_active && 
               $this->expires_at->isFuture() && 
               $this->canMakeRequest();
    }

    /**
     * Check rate limiting
     */
    public function canMakeRequest()
    {
        // Reset counter if more than 1 hour has passed
        if ($this->last_used_at && $this->last_used_at->diffInHours(now()) >= 1) {
            $this->update(['requests_count' => 0]);
        }

        return $this->requests_count < $this->requests_limit;
    }

    /**
     * Record API usage
     */
    public function recordUsage()
    {
        $this->increment('requests_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get remaining requests
     */
    public function getRemainingRequests()
    {
        return max(0, $this->requests_limit - $this->requests_count);
    }

    /**
     * Check if token has scope
     */
    public function hasScope($scope)
    {
        return in_array($scope, $this->scopes ?? []);
    }
}
