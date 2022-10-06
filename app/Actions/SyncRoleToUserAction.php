<?php

namespace App\Actions;

use App\Actions\Contracts\SyncRoleToUser;
use App\Models\User;
use Modules\Grantify\Facades\Grantify;
use Spatie\Permission\Models\Role;

class SyncRoleToUserAction implements SyncRoleToUser
{
    /**
     * @param  User  $user
     * @param  string|array|Role  $role
     * @return void
     */
    public function handle(User $user, string|array|Role $role): void
    {
        Grantify::syncRoleToModel($user, $role);
    }
}
