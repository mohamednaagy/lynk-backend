<?php

namespace App\Actions;

use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\UpdateAdminWithRoleAndPermission;
use App\Actions\Contracts\UpdateUser;
use App\Models\User;

class UpdateAdminWithRoleAndPermissionAction implements UpdateAdminWithRoleAndPermission
{
    public function __construct(
        protected UpdateUser $updateUser,
        protected SyncRoleToUser $syncRoleToUser,
        protected SyncPermissionToUser $syncPermissionToUser
    ) {}

    /**
     * Create new user.
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
            $this->syncPermissionToUser->handle($user, $data['permissions']);
        }
    }
}
