<?php

namespace App\Actions\Contracts\Lenders;

use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\UpdateUser;
use App\Models\User;

interface UpdateLenderUserWithRoleAndPermission
{
    /**
     * @param  UpdateUser  $updateUser
     * @param  SyncRoleToUser  $syncRoleToUser
     * @param  SyncPermissionToUser  $syncPermissionToUser
     */
    public function __construct(
        UpdateUser $updateUser,
        SyncRoleToUser $syncRoleToUser,
        SyncPermissionToUser $syncPermissionToUser
    );

    /**
     * Update user.
     *
     * @param  array  $data
     * @return User   $user
     */
    public function handle(array $data, User $user): void;
}
