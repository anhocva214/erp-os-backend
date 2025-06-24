<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    /**
     * Cache times in seconds
     */
    const CACHE_TIMES = [
        'short' => 300,      // 5 minutes
        'medium' => 900,     // 15 minutes  
        'long' => 3600,      // 1 hour
        'very_long' => 86400 // 24 hours
    ];

    /**
     * Get cached data with automatic key generation
     */
    public static function remember($key, $duration, $callback)
    {
        $cacheTime = is_string($duration) ? self::CACHE_TIMES[$duration] : $duration;
        return Cache::remember($key, $cacheTime, $callback);
    }

    /**
     * Clear cache by pattern
     */
    public static function clearByPattern($pattern)
    {
        $keys = [
            "products_*",
            "customers_*", 
            "suppliers_*",
            "sale_invoice_*",
            "dashboard_*",
            "role_permissions_*"
        ];

        foreach ($keys as $key) {
            if (str_contains($key, $pattern)) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Clear all optimization caches
     */
    public static function clearAllOptimizationCaches()
    {
        $cacheKeys = [
            'products_all_optimized',
            'products_fast_all',
            'products_info_fast',
            'customers_all_optimized',
            'suppliers_all_optimized',
            'sale_invoice_info_aggregation',
            'app_settings'
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get cache statistics
     */
    public static function getStats()
    {
        // This would require Redis or Memcached for detailed stats
        return [
            'cache_driver' => config('cache.default'),
            'optimization_active' => true,
            'last_cleared' => Cache::get('last_cache_clear', 'Never')
        ];
    }
}
