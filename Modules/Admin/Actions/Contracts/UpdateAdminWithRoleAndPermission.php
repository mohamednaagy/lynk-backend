<?php

namespace Modules\Admin\Actions\Contracts;

use App\Models\User;
use App\Actions\Contracts\UpdateUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\SyncPermissionToUser;

interface UpdateAdminWithRoleAndPermission
{
    /**
     * @param UpdateUser $updateUser
     * @param SyncRoleToUser $syncRoleToUser
     * @param SyncPermissionToUser $syncPermissionToUser
     */
    public function __construct(UpdateUser $updateUser, SyncRoleToUser $syncRoleToUser, SyncPermissionToUser $syncPermissionToUser);

    /**
     * Create new user.
     * @param array $data
     * @param User $user
     * @return void
     */
    public function __invoke(array $data, User $user): void;
}
