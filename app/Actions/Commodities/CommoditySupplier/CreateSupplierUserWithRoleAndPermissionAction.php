<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\Commodities\CommoditySupplier\CreateSupplierUserWithRoleAndPermission;
use App\Actions\Contracts\CreateUser;
use App\Models\User;
use Illuminate\Support\Arr;

class CreateSupplierUserWithRoleAndPermissionAction implements CreateSupplierUserWithRoleAndPermission
{
    /**
     * @param  CreateUser  $createUser
     * @param  AssignRoleToUser  $assignRoleToUser
     * @param  AssignPermissionToUser  $assignPermissionToUser
     */
    public function __construct(
        protected CreateUser $createUser,
        protected AssignRoleToUser $assignRoleToUser,
        protected AssignPermissionToUser $assignPermissionToUser
    ) {
    }

    /**
     * Create new user.
     *
     * @param  array  $data
     * @return User
     */
    public function handle(array $data): User
    {
        $user = $this->createUser->handle(Arr::only(
            $data,
            [
                'first_name',
                'last_name',
                'email',
                'phone_country_code',
                'phone_number',
                'commodity_supplier_id',
                'password',
                'is_active',
            ]
        ));

        // assign role to user
        if (! empty($data['role'])) {
            $this->assignRoleToUser->handle($user, $data['role']);
        }

        // assign permissions to user
        if (! empty($data['permissions'])) {
            $this->assignPermissionToUser->handle($user, $data['permissions']);
        }

        // return user
        return $user;
    }
}
