<?php

namespace App\Actions;

use App\Actions\Contracts\LoginUser;
use App\Http\Middleware\EnsureFrontendRequestsAreStatefulWithoutCookie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class LoginUserAction implements LoginUser
{
    public function handle(User $user, string $source = null, Request $request = null): array
    {
        $auth = [];

        if (App::runningInConsole() || EnsureFrontendRequestsAreStatefulWithoutCookie::fromFrontend(request()) === false) {
            $auth['token'] = $user->createToken($source)->plainTextToken;
            $auth['type'] = 'token';
        } else {
            Auth::login($user);
            $auth['token'] = $user->createToken($source)->plainTextToken;

            //            request()->session()->regenerate();
            $auth['type'] = 'session';
        }

        $auth['company_id'] = $user->company_id;

        return $auth;
    }
}
