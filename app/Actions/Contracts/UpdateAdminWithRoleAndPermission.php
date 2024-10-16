<?php

namespace App\Actions\Contracts;

use App\Actions\Contracts\Admins\AddAdminToAssignedList;
use App\Actions\Contracts\Admins\RemoveAdminFromAssignedList;
use App\Models\User;

interface UpdateAdminWithRoleAndPermission
{
    /**
     * @param  SyncRoleToUser  $syncRoleToUser
     * @param  SyncPermissionToUser  $syncPermissionToUser
     */
    public function __construct(UpdateUser $updateUser, SyncRoleToUser $syncRoleToUser, SyncPermissionToUser $syncPermissionToUser, AddAdminToAssignedList $addAdminToAssignedList, RemoveAdminFromAssignedList $removeAdminFromAssignedList);

    /**
     * Create new user.
     *
     * @param  array  $data
     * @param  User  $user
     * @return void
     */
    public function handle(array $data, User $user): void;
}
