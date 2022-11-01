<?php

namespace App\Actions\Contracts\Admins\Auth;

use App\Models\User;

interface UpdateAdminUser
{
    /**
     * @param  User  $user
     * @param  array  $data
     * @return bool
     */
    public function handle(User $user, array $data): bool;
}
