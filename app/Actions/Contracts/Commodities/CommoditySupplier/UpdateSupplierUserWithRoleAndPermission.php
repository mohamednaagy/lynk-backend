<?php

namespace App\Actions\Contracts\Commodities\CommoditySupplier;

use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\UpdateUser;
use App\Models\User;

interface UpdateSupplierUserWithRoleAndPermission
{
    public function __construct(
        UpdateUser $updateUser,
        SyncRoleToUser $syncRoleToUser,
        SyncPermissionToUser $syncPermissionToUser
    );

    /**
     * Update user.
     *
     * @return void $user
     */
    public function handle(array $data, User $user): void;
}
