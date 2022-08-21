<?php

namespace App\Actions;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Modules\Permission\Facades\Grantify;
use App\Actions\Contracts\AssignPermissionToUser;

class AssignPermissionToUserAction implements AssignPermissionToUser
{
    /**
     * @param User $user
     * @param string|array|Permission $permission
     * @return void
     */
    public function handle(User $user, string|array|Permission $permission): void
    {
        Grantify::assignPermissionToModel($user, $permission);
    }
}
