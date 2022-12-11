<?php

namespace App\Actions\Contracts\Auth;

use App\Models\User;

interface UpdateMyProfile
{
    public function handle(User $user, array $data, string $area): User;
}
