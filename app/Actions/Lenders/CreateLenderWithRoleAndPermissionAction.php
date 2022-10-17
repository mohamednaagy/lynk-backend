<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\CreateUser;
use App\Actions\Contracts\Lenders\CreateLenderWithRoleAndPermission;
use App\Models\User;
use Illuminate\Support\Arr;

class CreateLenderWithRoleAndPermissionAction implements CreateLenderWithRoleAndPermission
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
        // create user
        $user = $this->createUser->handle(Arr::only(
            $data,
            [
                'first_name',
                'last_name',
                'email',
                'phone_country_code',
                'phone_number',
                'password',
            ]
        ));

        // assign role to user
        if (!empty($data['role'])) {
            $this->assignRoleToUser->handle($user, $data['role']);
        }
        // return user
        return $user;
    }
}
