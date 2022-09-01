<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Otpify\Facades\Otpify;
use App\Actions\Contracts\GetSettingsClassInstance;

class VerifyAuthorization
{

    public function __construct(protected GetSettingsClassInstance $getSettingsClassInstance)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $area
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next, string $area): JsonResponse
    {
        $setting = $this->getSettingsClassInstance->handle($area);

        if (!$setting->otp_enabled)
            return $next($request);

        if ($request->has('authorized_token') && Otpify::verifyAuthorizationToken($request->input('authorized_token')))
            return $next($request);

        if ($request->expectsJson())
            return response()->errorResponse('User not authorized', 403); // or return false

        abort(403);
    }
}
