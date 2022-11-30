<?php

namespace App\Actions;

use App\Actions\Contracts\AssignPermissionToUser;
use App\Models\User;
use Modules\Grantify\Facades\Grantify;
use Spatie\Permission\Models\Permission;

class AssignPermissionToUserAction implements AssignPermissionToUser
{
    /**
     * @param  User  $user
     * @param  string|array|Permission  $permission
     * @return void
     */
    public function handle(User $user, string|array|Permission $permission): void
    {
        Grantify::assignPermissionToModel($user, $permission);
    }
}
