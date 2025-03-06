<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

class CustomThrottleRequests extends ThrottleRequests
{
    public function handle($request, Closure $next, ...$parameters)
    {
        if (config('app.disable_rate_limiter')) {
            return $next($request);
        }

        return parent::handle($request, $next, ...$parameters);
    }
}
