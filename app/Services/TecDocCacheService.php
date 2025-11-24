<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TecDocCacheService
{
    // Cache TTL constants (in seconds)
    const SEARCH_CACHE_TTL = 1800; // 30 minutes
    const BRANDS_CACHE_TTL = 7200; // 2 hours
    const VEHICLE_DATA_TTL = 14400; // 4 hours
    const ARTICLE_DETAILS_TTL = 3600; // 1 hour

    /**
     * Generate cache key for search results
     */
    public static function getSearchCacheKey($searchData, $page = 1): string
    {
        $keyData = [
            'vehicle_type' => $searchData['vehicle_type'] ?? '',
            'manufacturer_id' => $searchData['manufacturer_id'] ?? '',
            'model_series_id' => $searchData['model_series_id'] ?? '',
            'vehicle_version_id' => $searchData['vehicle_version_id'] ?? '',
            'part_category' => $searchData['part_category'] ?? '',
            'page' => $page
        ];
        
        return 'search:' . md5(json_encode($keyData));
    }

    /**
     * Generate cache key for available brands
     */
    public static function getBrandsCacheKey($searchData): string
    {
        $keyData = [
            'vehicle_type' => $searchData['vehicle_type'] ?? '',
            'manufacturer_id' => $searchData['manufacturer_id'] ?? '',
            'model_series_id' => $searchData['model_series_id'] ?? '',
            'vehicle_version_id' => $searchData['vehicle_version_id'] ?? '',
            'part_category' => $searchData['part_category'] ?? ''
        ];
        
        return 'brands:' . md5(json_encode($keyData));
    }

    /**
     * Cache search results
     */
    public static function cacheSearchResults($cacheKey, $data, $ttl = null): void
    {
        $ttl = $ttl ?? self::SEARCH_CACHE_TTL;
        
        try {
            Cache::put($cacheKey, $data, $ttl);
            Log::info("Cached search results", ['key' => $cacheKey, 'ttl' => $ttl]);
        } catch (\Exception $e) {
            Log::error("Failed to cache search results", [
                'key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached search results
     */
    public static function getCachedSearchResults($cacheKey)
    {
        try {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::info("Cache hit for search results", ['key' => $cacheKey]);
                return $cached;
            }
        } catch (\Exception $e) {
            Log::error("Failed to get cached search results", [
                'key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Cache available brands
     */
    public static function cacheBrands($cacheKey, $brands, $ttl = null): void
    {
        $ttl = $ttl ?? self::BRANDS_CACHE_TTL;
        
        try {
            Cache::put($cacheKey, $brands, $ttl);
            Log::info("Cached brands", ['key' => $cacheKey, 'count' => count($brands)]);
        } catch (\Exception $e) {
            Log::error("Failed to cache brands", [
                'key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached brands
     */
    public static function getCachedBrands($cacheKey)
    {
        try {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                Log::info("Cache hit for brands", ['key' => $cacheKey]);
                return $cached;
            }
        } catch (\Exception $e) {
            Log::error("Failed to get cached brands", [
                'key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Cache vehicle data (manufacturers, models, etc.)
     */
    public static function cacheVehicleData($key, $data, $ttl = null): void
    {
        $ttl = $ttl ?? self::VEHICLE_DATA_TTL;
        
        try {
            Cache::put("vehicle:{$key}", $data, $ttl);
            Log::info("Cached vehicle data", ['key' => $key]);
        } catch (\Exception $e) {
            Log::error("Failed to cache vehicle data", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached vehicle data
     */
    public static function getCachedVehicleData($key)
    {
        try {
            return Cache::get("vehicle:{$key}");
        } catch (\Exception $e) {
            Log::error("Failed to get cached vehicle data", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache article details
     */
    public static function cacheArticleDetails($articleId, $details, $ttl = null): void
    {
        $ttl = $ttl ?? self::ARTICLE_DETAILS_TTL;
        
        try {
            Cache::put("article:{$articleId}", $details, $ttl);
            Log::info("Cached article details", ['article_id' => $articleId]);
        } catch (\Exception $e) {
            Log::error("Failed to cache article details", [
                'article_id' => $articleId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cached article details
     */
    public static function getCachedArticleDetails($articleId)
    {
        try {
            return Cache::get("article:{$articleId}");
        } catch (\Exception $e) {
            Log::error("Failed to get cached article details", [
                'article_id' => $articleId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Clear all TecDoc caches
     */
    public static function clearAllCaches(): void
    {
        try {
            // Clear search caches
            $pattern = 'search:*';
            self::clearCachePattern($pattern);
            
            // Clear brands caches
            $pattern = 'brands:*';
            self::clearCachePattern($pattern);
            
            // Clear vehicle caches
            $pattern = 'vehicle:*';
            self::clearCachePattern($pattern);
            
            // Clear article caches
            $pattern = 'article:*';
            self::clearCachePattern($pattern);
            
            Log::info("Cleared all TecDoc caches");
        } catch (\Exception $e) {
            Log::error("Failed to clear caches", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Clear cache by pattern (Redis specific)
     */
    private static function clearCachePattern($pattern): void
    {
        try {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);
            
            if (!empty($keys)) {
                $redis->del($keys);
                Log::info("Cleared cache pattern", ['pattern' => $pattern, 'count' => count($keys)]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to clear cache pattern", [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStats(): array
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info();
            
            return [
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $info['keyspace_hits'] && $info['keyspace_misses'] 
                    ? round($info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses']) * 100, 2) . '%'
                    : 'N/A'
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get cache stats", ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage()];
        }
    }
}
