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
        $tokenTtl = $company?->lender?->lenderDetail?->token_expire_in;
        $token = $user->createToken($source);
        $accessToken = $token->accessToken;
        if ($company && $tokenTtl && $user->hasRole(Role::LenderApiUser)) {
            $accessToken->expire_at = now()->addSeconds($tokenTtl);
            $accessToken->save();
        }

        return [
            'type' => 'token',
            'token' => $token->plainTextToken,
            'company_id' => $company?->id,
            'expire_in' => $tokenTtl,
        ];
    }
}
