<?php

namespace App\Actions;

use App\Actions\Contracts\FindUserByIdAndRole;
use App\Models\User;

class FindUserByIdAndRoleAction implements FindUserByIdAndRole
{
    /**
     * @param int $id
     * @param string $role
     * @return User|null
     */
    public function handle(int $id, string $role): ?User
    {
        return  User::role($role)->find($id);
    }
}
