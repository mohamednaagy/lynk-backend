<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

class ThrottleManager extends ThrottleRequests
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        if (config('app.enable_rate_limiter')) {
            $rateLimiterAttempts = config('rate_limiter', []);
            $routeKey = $request->route()->getName() ?: $request->path();
            $maxAttempts = $rateLimiterAttempts[$routeKey] ?? $maxAttempts;

            return parent::handle($request, $next, $maxAttempts, $decayMinutes, $routeKey);
        }

        return $next($request);
    }
}
