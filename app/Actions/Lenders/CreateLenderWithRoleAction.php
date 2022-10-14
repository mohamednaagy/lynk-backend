<?php

namespace App\Actions\Lenders;

use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\CreateUser;
use App\Actions\Contracts\Lenders\CreateLenderWithRole;
use App\Models\User;
use DragonCode\Support\Facades\Helpers\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class CreateLenderWithRoleAction implements CreateLenderWithRole
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
        $data['password'] = Hash::make($data['password']);
        $data['phone_number'] = phone($data['phone_number'], $data['phone_country_code']);
        $user = User::create(Arr::only(
            $data,
            [
                'first_name',
                'last_name',
                'email',
                'phone_number',
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
