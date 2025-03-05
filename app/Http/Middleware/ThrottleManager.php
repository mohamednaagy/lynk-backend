<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;

class ThrottleManager extends ThrottleRequests
{
    public function handle($request, Closure $next, $limiterName = null, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        if (config('app.api_throttle_enabled')) {

            if (RateLimiter::limiter($limiterName)) {
                $limiter = RateLimiter::limiter($limiterName);

                return parent::handleRequestUsingNamedLimiter($request, $next, $limiterName, $limiter);
            }

            // $maxAttempts is set to the value of $limiterName,
            // assuming the limiter name is a numeric value (e.g., '5,1', where '5' represents the maximum attempts
            // and '1' represents the decay time in minutes).
            $decayMinutes = $maxAttempts;
            $maxAttempts = $limiterName;

            // If no named limiter is found, apply the default global throttle settings
            return parent::handleRequest($request, $next,
            [
                (object) [
                    'key' => $prefix.parent::resolveRequestSignature($request),
                    'maxAttempts' => parent::resolveMaxAttempts($request, $maxAttempts),
                    'decayMinutes' => $decayMinutes,
                    'responseCallback' => null,
                ],
            ]);
        }

        return $next($request);
    }
}
