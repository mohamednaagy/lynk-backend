<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\Lenders\UpdateLenderUserWithRoleAndPermission;
use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\UpdateUser;
use App\Models\User;
use Illuminate\Support\Arr;

class UpdateLenderUserWithRoleAndPermissionAction implements UpdateLenderUserWithRoleAndPermission
{
    /**
     * @param  UpdateUser  $updateUser
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
     * Update user.
     *
     * @param  array  $data
     * @param  User  $user
     * @return void $user
     */
    public function handle(array $data, User $user): void
    {
        // Update user
        $this->updateUser->handle($user, Arr::only(
            $data,
            [
                'first_name',
                'last_name',
                'email',
            ]
        ));

        // sync role
        if (! empty($data['role'])) {
            $this->syncRoleToUser->handle($user, $data['role']);
        }

        // assign permissions
        if (! empty($data['permissions'])) {
            $this->syncPermissionToUser->handle($user, $data['permissions']);
        }
    }
}
