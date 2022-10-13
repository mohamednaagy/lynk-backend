<?php

namespace App\Actions;

use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\CreateLenderWithRoleAndPermission;
use App\Actions\Contracts\CreateUser;
use App\Enums\Role;
use App\Models\User;
use DragonCode\Support\Facades\Helpers\Arr;
use Illuminate\Support\Facades\Hash;
use Modules\Grantify\Facades\Grantify;
use Illuminate\Support\Str;


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
        $data['password'] = Hash::make(Str::random());
        $data['phone_number'] = phone($data['phone_number'], $data['phone_country_code']);

        $user = User::create(
            Arr::only(
                $data,
                [
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'password',
                    'role',
                ]
            )
        );
        Grantify::assignRoleToModel($user, Role::LenderAdmin);
        return $user;
    }
}
