<?php

namespace App\Http\Middleware;

use App\Actions\Contracts\GetSettingsClassInstance;
use App\Enums\Area;
use App\Enums\ErrorCode;
use Closure;
use Illuminate\Http\Request;

class IsEmailVerified
{
    protected GetSettingsClassInstance $getSettingsClassInstance;

    public function __construct(GetSettingsClassInstance $getSettingsClassInstance)
    {
        $this->getSettingsClassInstance = $getSettingsClassInstance;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $area)
    {
        $user = $request->user();
        if (! $user) {
            return $this->notAuthorizedResponse($request);
        }
        if ($area == Area::Lender) {
            if ($this->isEmailVerifiedRequired($area) && ! $user->hasVerifiedEmail()) {
                return $this->notAuthorizedResponse($request);
            }
        } elseif ($area == Area::CommoditySupplier) {
            if (! $user->hasVerifiedEmail()) {
                return $this->notAuthorizedResponse($request);
            }
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
        $message = __('error.must_verify_email');
        if ($request->expectsJson()) {
            return response()->errorResponse($message, 403, ErrorCode::EMAIL_NOT_VERIFIED);
        }

        abort(403, $message);
    }
}
