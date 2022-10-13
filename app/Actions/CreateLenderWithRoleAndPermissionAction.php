<?php

namespace App\Actions;

use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\CreateLenderWithRoleAndPermission;
use App\Actions\Contracts\CreateUser;
use App\Models\User;
use DragonCode\Support\Facades\Helpers\Arr;

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
        //data
        $data = Arr::only('first_name', 'last_name', 'phone_country_code', 'phone_number', 'email', 'password', 'password_confirmation' . 'role');
        // create user
        $user = $this->createUser->handle($data);

        // assign role to user
        if (!empty($data['role'])) {
            $this->assignRoleToUser->handle($user, $data['role']);
        }


        // return user
        return $user;
    }
}
