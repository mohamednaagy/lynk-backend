<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface UpdateAdminWithRoleAndPermission
{
    public function __construct(UpdateUser $updateUser, SyncRoleToUser $syncRoleToUser, SyncPermissionToUser $syncPermissionToUser);

    /**
     * Create new user.
     */
    public function handle(array $data, User $user): void;
}
