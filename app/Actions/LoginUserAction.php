<?php

namespace App\Actions;

use App\Actions\Contracts\LoginUser;
use App\Models\User;
use App\Services\JWTService;
use Illuminate\Http\Request;

class LoginUserAction implements LoginUser
{
    public function __construct(protected JWTService $jwtService) {}

    public function handle(User $user, ?string $source = null, ?Request $request = null): array
    {
        return $this->jwtService->generateToken($user);
    }
}
