<?php

namespace App\Actions;

use App\Actions\Contracts\LoginUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class LoginUserAction implements LoginUser
{
    public function handle(User $user, ?string $source = null, ?Request $request = null): array
    {
        $auth = [];

        if (App::runningInConsole() || EnsureFrontendRequestsAreStateful::fromFrontend(request()) === false) {
            $auth['token'] = $user->createToken($source)->plainTextToken;
            $auth['type'] = 'token';
        } else {
            Auth::login($user);
            request()->session()->regenerate();
            $auth['type'] = 'session';
        }

        $auth['company_id'] = $user->company_id;

        return $auth;
    }
}
