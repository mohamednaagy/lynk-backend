<?php

namespace App\Actions;

use App\Actions\Contracts\LoginUser;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;

class LoginUserAction implements LoginUser
{
    public function handle(User $user, ?string $source = null, ?Request $request = null): array
    {
        $company = $user->company;
        $token = $user->createToken($source);
        $accessToken = $token->accessToken;
        $tokenTtl = null;
        if ($user->hasRole(Role::LenderApiUser) && ! is_null($company->getTokenExpireValue())) {
            $tokenTtl = $company->getTokenExpireValue();
            $accessToken->expire_at = now()->addSeconds($tokenTtl);
            $accessToken->save();
        }

        return [
            'type' => 'token',
            'token' => $token->plainTextToken,
            'company_id' => $company?->id,
            'expires_in' => $tokenTtl,
        ];
    }
}
