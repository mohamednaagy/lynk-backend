<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class JWTService
{
    public function generateToken(User $user): array
    {
        [$ttlMinutes, $expiresIn] = $this->getTtl($user);
        $version = $this->getTokenVersion($user);

        JWTAuth::factory()->setTTL($ttlMinutes);
        $token = JWTAuth::claims(['version' => $version])->fromUser($user);

        return [
            'type' => 'token',
            'token' => $token,
            'company_id' => $user->company?->id,
            'expires_in' => $expiresIn,
        ];
    }

    public function getTtl(User $user): array
    {
        $defaultTtl = 10 * 365 * 24 * 60; // 10 years

        if (
            $user->hasRole(Role::LenderApiUser) &&
            ($customTtl = $user->company?->getTokenExpireValue())
        ) {
            return [$customTtl, $customTtl * 60];
        }

        return [$defaultTtl, null];
    }

    public function getTokenVersion(User $user): int
    {
        if ($user->hasRole(Role::LenderApiUser)) {
            return $user->company->getTokenExpireVersion();
        }

        return 1;
    }
}
