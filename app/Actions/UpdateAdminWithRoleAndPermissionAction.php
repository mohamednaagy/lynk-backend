<?php

namespace App\Actions;

use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\UpdateAdminWithRoleAndPermission;
use App\Actions\Contracts\UpdateUser;
use App\Enums\Area;
use App\Models\User;
use Modules\Grantify\Facades\Grantify;

class UpdateAdminWithRoleAndPermissionAction implements UpdateAdminWithRoleAndPermission
{
    /**
     * @param  SyncRoleToUser  $syncRoleToUser
     * @param  SyncPermissionToUser  $syncPermissionToUser
     */
    public function __construct(
        protected UpdateUser $updateUser,
        protected SyncRoleToUser $syncRoleToUser,
        protected SyncPermissionToUser $syncPermissionToUser
    ) {
    }

    /**
     * Create new user.
     *
     * @param  array  $data
     * @param  User  $user
     * @return void
     */
    public function handle(array $data, User $user): void
    {
        // update user
        $this->updateUser->handle($user, $data);

        // sync role
        if (! empty($data['role'])) {
            $this->syncRoleToUser->handle($user, $data['role']);
        }

        // sync permission
        if (! empty($data['permissions'])) {
            $data['permissions'] = Grantify::transformToAreaSubject(Area::SuperAdmin, $data['permissions']);
            $this->syncPermissionToUser->handle($user, $data['permissions']);
        }
    }
}
