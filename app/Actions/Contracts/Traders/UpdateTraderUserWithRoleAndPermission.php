<?php

namespace App\Actions\Contracts\Traders;

use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\UpdateUser;
use App\Models\User;

interface UpdateTraderUserWithRoleAndPermission
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
     * @param  User  $user
     * @return void $user
     */
    public function handle(array $data, User $user): void;
}
