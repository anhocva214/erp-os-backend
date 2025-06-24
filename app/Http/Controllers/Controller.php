<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Get optimized pagination with caching
     */
    protected function getOptimizedPagination($query, $cacheKey, $cacheTime = 300)
    {
        return Cache::remember($cacheKey, $cacheTime, function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Get cached aggregation
     */
    protected function getCachedAggregation($model, $cacheKey, $aggregations, $cacheTime = 600)
    {
        return Cache::remember($cacheKey, $cacheTime, function () use ($model, $aggregations) {
            return $model::selectRaw($aggregations)->first();
        });
    }

    /**
     * Optimize eager loading with select
     */
    protected function optimizeEagerLoading($query, $relations)
    {
        foreach ($relations as $relation => $columns) {
            if (is_array($columns)) {
                $query->with([$relation => function ($q) use ($columns) {
                    $q->select($columns);
                }]);
            } else {
                $query->with($relation);
            }
        }
        return $query;
    }

    /**
     * Fast API response with minimal data
     */
    protected function fastApiResponse($model, $cacheKey, $select = ['id', 'name'], $cacheTime = 1800)
    {
        return Cache::remember($cacheKey, $cacheTime, function () use ($model, $select) {
            return $model::select($select)->get();
        });
    }
}
