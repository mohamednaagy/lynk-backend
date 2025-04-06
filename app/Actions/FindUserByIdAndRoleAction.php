<?php

namespace App\Actions;

use App\Actions\Contracts\FindUserByIdAndRole;
use App\Models\User;

class FindUserByIdAndRoleAction implements FindUserByIdAndRole
{
    public function handle(int $id, string $role): ?User
    {
        return User::role($role)->find($id);
    }
}
