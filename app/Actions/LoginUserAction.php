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
    /**
     * @param User $user
     * @param string|null $source
     * @param Request|null $request
     * @return array
     */
    public function handle(User $user, string $source = null, Request $request = null): array
    {
        $auth = ['type' => null];

        if (App::runningInConsole() || false === EnsureFrontendRequestsAreStateful::fromFrontend(request())) {
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
