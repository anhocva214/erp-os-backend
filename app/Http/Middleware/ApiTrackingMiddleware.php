<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class ApiTrackingMiddleware
{
    public function handle($request, Closure $next)
    {
        // Log the request information
        Log::info('API Request', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'parameters' => $request->all(),
            'user_agent' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
        ]);

        return $next($request);
    }
}
