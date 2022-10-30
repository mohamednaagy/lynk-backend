<?php

namespace App\Http\Middleware;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\ErrorCode;
use Closure;
use Illuminate\Http\Request;

class IsEmailVerified
{
    protected $getSettingsClassInstance;

    public function __construct(GetSettingsClassInstance $getSettingsClassInstance)
    {
        $this->getSettingsClassInstance = $getSettingsClassInstance;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $area)
    {
        $user = $request->user();

        if (! $user || ($this->isEmailVerifiedRequired($area) && ! $user->hasVerifiedEmail())) {
            return $this->notAuthorizedResponse($request);
        }

        return $next($request);
    }

    private function isEmailVerifiedRequired($area)
    {
        $setting = $this->getSettingsClassInstance->handle($area);

        return isset($setting->email_verification_enabled) && (bool) $setting->email_verification_enabled;
    }

    private function notAuthorizedResponse(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->errorResponse(__('Must Verify Email'), ErrorCode::EMAIL_NOT_VERIFIED);
        }

        abort(403);
    }
}
