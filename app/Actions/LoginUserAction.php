<?php

namespace App\Actions;

use App\Actions\Contracts\LoginUser;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginUserAction implements LoginUser
{
    public function handle(User $user, ?string $source = null, ?Request $request = null): array
    {
        $company = $user->company;

        $tokenTtlSeconds = null;

        if (
            $user->hasRole(Role::LenderApiUser) &&
            ! is_null($company?->getTokenExpireValue())
        ) {
            $customTtlMinutes = $company->getTokenExpireValue();
            $tokenTtlSeconds = $customTtlMinutes * 60;

            JWTAuth::factory()->setTTL($customTtlMinutes);
        } else {
            // Disable expiration (no exp claim in the token)
            JWTAuth::factory()->setTTL(10 * 365 * 24 * 60); // 10 years in minutes
        }

        $token = JWTAuth::fromUser($user);

        return [
            'type' => 'token',
            'token' => $token,
            'company_id' => $company?->id,
            'expires_in' => $tokenTtlSeconds, // could be null
        ];
    }
}
