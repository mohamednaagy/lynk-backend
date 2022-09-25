<?php

namespace App\Actions;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Grantify\Facades\Grantify;
use App\Actions\Contracts\AssignRoleToUser;

class AssignRoleToUserAction implements AssignRoleToUser
{
    /**
     * @param User $user
     * @param string|array|Role $role
     * @return void
     */
    public function handle(User $user, string|array|Role $role): void
    {
        Grantify::assignRoleToModel($user, $role);
    }
}
