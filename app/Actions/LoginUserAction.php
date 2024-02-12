<?php

namespace App\Actions;

use App\Actions\Contracts\LoginUser;
use App\Models\User;
use Illuminate\Http\Request;

class LoginUserAction implements LoginUser
{
    public function handle(User $user, string $source = null, Request $request = null): array
    {
        $auth = [];
        $auth['token'] = $user->createToken($source)->plainTextToken;
        $auth['type'] = 'token';
        $auth['company_id'] = $user->company_id;

        return $auth;
    }
}
