<?php

namespace App\Services\Tokens\Config;

use App\Enums\Role;
use App\Models\User;

class TokenConfigResolver
{
    public function getTtlInSeconds(User $user): ?int
    {
        if (
            $user->hasRole(Role::LenderApiUser) &&
            ($ttl = $user->lender?->getTokenExpireValue())
        ) {
            return $ttl * 60;
        }

        return null;
    }

    public function getTokenVersion(User $user): int
    {
        if ($user->hasRole(Role::LenderApiUser)) {
            return $user->lender?->getTokenExpireVersion() ?? 1;
        }

        return 1;
    }

    public function getTokenIssuer(): string
    {
        return config('app.name');
    }
}
