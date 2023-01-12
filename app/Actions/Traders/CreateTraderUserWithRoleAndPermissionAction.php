<?php

namespace App\Actions\Traders;

use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\CreateUser;
use App\Actions\Contracts\Traders\CreateTraderUserWithRoleAndPermission;
use App\Models\User;
use Illuminate\Support\Arr;

class CreateTraderUserWithRoleAndPermissionAction implements CreateTraderUserWithRoleAndPermission
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
                'company_id',
                'phone_number',
                'password',
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
