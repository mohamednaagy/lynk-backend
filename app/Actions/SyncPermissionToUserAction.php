<?php

namespace App\Actions;

use App\Models\User;
use Modules\Grantify\Facades\Grantify;
use Spatie\Permission\Models\Permission;
use App\Actions\Contracts\SyncPermissionToUser;

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
