<?php

namespace App\Http\Middleware;

use App\Actions\Contracts\GetSettingsClassInstance;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Otpify\Facades\Otpify;

class CheckAreaOtp
{
    public function __construct(
        protected GetSettingsClassInstance $getSettingsClassInstance
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string  $area
     * @return JsonResponse|Response
     */
    public function handle(Request $request, Closure $next, string $area): JsonResponse|Response
    {
        $setting = $this->getSettingsClassInstance->handle($area);

        if (
            (! $setting->otp_enabled)
            ||
            ($request->headers->has('authorized_token') && Otpify::verifyAuthorizationToken($request->header('authorized_token'), $area))
        ) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->errorResponse('User not authorized', 403); // or return false
        }

        abort(403);
    }
}
