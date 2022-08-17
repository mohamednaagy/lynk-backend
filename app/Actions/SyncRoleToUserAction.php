<?php

namespace App\Actions;

use App\Actions\Contracts\SyncRoleToUser;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Modules\Permission\Facades\Grantify;

class SyncRoleToUserAction implements SyncRoleToUser
{
    /**
     * @param User $user
     * @param string|array|Role $role
     * @return void
     */
    public function __invoke(User $user, string|array|Role $role): void
    {
        Grantify::syncRoleToModel($user, $role);
    }
}
