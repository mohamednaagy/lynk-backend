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
            // __REVIEW__ user lang/{ar|en}/error.php file for translation
            // __REVIEW__ Arabic: يجب عليك التحقق من البريد الإلكتروني
            // __REVIEW__ English: You must verify your email address
            return response()->errorResponse(__('Must Verify Email'), 403, ErrorCode::EMAIL_NOT_VERIFIED);
        }

        abort(403);
    }
}
