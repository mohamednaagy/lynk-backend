<?php

namespace App\Actions;

use App\Actions\Contracts\SyncPermissionToUser;
use App\Models\User;
use Modules\Grantify\Facades\Grantify;
use Spatie\Permission\Models\Permission;

class SyncPermissionToUserAction implements SyncPermissionToUser
{
    public function handle(User $user, string|array|Permission $permission): void
    {
        Grantify::syncPermissionToModel($user, $permission);
    }
}
