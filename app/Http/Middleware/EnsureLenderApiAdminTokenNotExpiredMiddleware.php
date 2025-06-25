<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;

class EnsureLenderApiAdminTokenNotExpiredMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        $token = $user?->currentAccessToken();
        if ($token) {
            $isUserHasLenderApiUserRole = $user->hasRole(Role::LenderApiUser);
            if ($isUserHasLenderApiUserRole) {
                if ($token->expire_at?->isPast()) {
                    return response()->json(['message' => __('Unauthenticated.')], 401);
                }
            }
        }

        return $next($request);
    }
}
