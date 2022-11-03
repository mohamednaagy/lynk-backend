<?php

namespace App\Actions;

use App\Actions\Contracts\Admins\UpdateAdminUser;
use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\UpdateAdminWithRoleAndPermission;
use App\Models\User;

class UpdateAdminWithRoleAndPermissionAction implements UpdateAdminWithRoleAndPermission
{
    /**
     * @param  SyncRoleToUser  $syncRoleToUser
     * @param  SyncPermissionToUser  $syncPermissionToUser
     */
    public function __construct(
        protected UpdateAdminUser $updateAdminUser,
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
        $this->updateAdminUser->handle($user, $data);

        // sync role
        if (! empty($data['role'])) {
            $this->syncRoleToUser->handle($user, $data['role']);
        }

        // sync permission
        if (! empty($data['permissions'])) {
            $this->syncPermissionToUser->handle($user, $data['permissions']);
        }
    }
}
