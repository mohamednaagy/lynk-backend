<?php

namespace App\Actions;

use App\Actions\Contracts\SyncPermissionToUser;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Modules\Permission\Facades\Grantify;

class SyncPermissionToUserAction implements SyncPermissionToUser
{
    /**
     * @param User $user
     * @param string|array|Permission $permission
     * @return void
     */
    public function handle(User $user, string|array|Permission $permission): void
    {
        Grantify::syncPermissionToModel($user, $permission);
    }
}
