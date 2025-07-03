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
        [$ttlMinutes, $expiresIn] = $this->getJwtTtlForUser($user);

        JWTAuth::factory()->setTTL($ttlMinutes);
        $token = JWTAuth::fromUser($user);

        return [
            'type' => 'token',
            'token' => $token,
            'company_id' => $user->company?->id,
            'expires_in' => $expiresIn,
        ];
    }

    /**
     * Get the JWT TTL (in minutes), and expiration time(in seconds) for the given user.
     *
     * @return array [$ttlMinutes, $expiresIn]
     */
    private function getJwtTtlForUser(User $user): array
    {
        $defaultTtl = 10 * 365 * 24 * 60; // 10 years in minutes

        if (
            $user->hasRole(Role::LenderApiUser) &&
            ($customTtl = $user->company?->getTokenExpireValue())
        ) {
            return [$customTtl, $customTtl * 60];
        }

        return [$defaultTtl, null];
    }
}
