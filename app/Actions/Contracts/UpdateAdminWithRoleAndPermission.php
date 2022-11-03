<?php

namespace App\Actions\Contracts;

use App\Actions\Contracts\Admins\UpdateAdminUser;
use App\Models\User;

interface UpdateAdminWithRoleAndPermission
{
    /**
     * @param  UpdateAdminUser  $updateAdminUser
     * @param  SyncRoleToUser  $syncRoleToUser
     * @param  SyncPermissionToUser  $syncPermissionToUser
     */
    public function __construct(UpdateAdminUser $updateAdminUser, SyncRoleToUser $syncRoleToUser, SyncPermissionToUser $syncPermissionToUser);

    /**
     * Create new user.
     *
     * @param  array  $data
     * @param  User  $user
     * @return void
     */
    public function handle(array $data, User $user): void;
}
