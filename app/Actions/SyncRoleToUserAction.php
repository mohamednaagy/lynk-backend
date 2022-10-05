<?php

namespace App\Actions;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Grantify\Facades\Grantify;
use App\Actions\Contracts\SyncRoleToUser;

class SyncRoleToUserAction implements SyncRoleToUser
{
    /**
     * @param User $user
     * @param string|array|Role $role
     * @return void
     */
    public function handle(User $user, string|array|Role $role): void
    {
        Grantify::syncRoleToModel($user, $role);
    }
}
