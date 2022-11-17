<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\CreateUser;
use App\Actions\Contracts\Lenders\CreateLenderUserWithRoleAndPermission;
use App\Models\User;
use Illuminate\Support\Arr;

class CreateLenderUserWithRoleAndPermissionAction implements CreateLenderUserWithRoleAndPermission
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
        // __REVIEW__ create user doesn't accept company_id so you should update CreateUserAction
        // create user
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
