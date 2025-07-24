<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class VerifyTokenVersion
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();

        if ($user && $user->hasRole(Role::LenderApiUser)) {

            $payload = JWTAuth::parseToken()->getPayload();
            $tokenVersionFromToken = $payload->get('version');
            $dbVersion = $user->company?->lender?->lenderDetail?->token_version ?? 1;

            if ($tokenVersionFromToken != $dbVersion) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }
        }

        return $next($request);
    }
}
