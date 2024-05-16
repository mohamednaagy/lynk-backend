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
    public function __construct(
        protected CreateUser $createUser,
        protected AssignRoleToUser $assignRoleToUser,
        protected AssignPermissionToUser $assignPermissionToUser
    ) {
    }

    /**
     * Create new user.
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
                'company_id',
                'phone_number',
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
