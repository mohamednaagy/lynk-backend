<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\Commodities\CommoditySupplier\UpdateSupplierUserWithRoleAndPermission;
use App\Actions\Contracts\SyncPermissionToUser;
use App\Actions\Contracts\SyncRoleToUser;
use App\Actions\Contracts\UpdateUser;
use App\Models\User;
use Illuminate\Support\Arr;

class UpdateSupplierUserWithRoleAndPermissionAction implements UpdateSupplierUserWithRoleAndPermission
{
    public function __construct(
        protected UpdateUser $updateUser,
        protected SyncRoleToUser $syncRoleToUser,
        protected SyncPermissionToUser $syncPermissionToUser
    ) {}

    /**
     * Update user.
     *
     * @return void $user
     */
    public function handle(array $data, User $user): void
    {
        // Update user
        $this->updateUser->handle(
            $user,
            Arr::only(
                $data,
                [
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'phone_country_code',
                ]
            )
        );

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
