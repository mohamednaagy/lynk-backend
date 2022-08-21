<?php

namespace App\Actions;

use App\Models\User;
use App\Actions\Contracts\CreateUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\AssignPermissionToUser;
use Modules\Admin\Actions\Contracts\CreateAdminWithRoleAndPermission;

class CreateAdminWithRoleAndPermissionAction implements CreateAdminWithRoleAndPermission
{
    /**
     * @param CreateUser $createUser
     * @param AssignRoleToUser $assignRoleToUser
     * @param AssignPermissionToUser $assignPermissionToUser
     */
    public function __construct(
        protected CreateUser $createUser,
        protected AssignRoleToUser $assignRoleToUser,
        protected AssignPermissionToUser $assignPermissionToUser
    )
    {
    }

    /**
     * Create new user.
     * @param array $data
     * @return User
     */
    public function handle(array $data): User
    {
        # create user
        $user = ($this->createUser)($data);

        # assign role to user
        if (!empty($data['role']))
            ($this->assignRoleToUser)($user, $data['role']);

        # assign permission to user
        if (!empty($data['permissions']))
            ($this->assignPermissionToUser)($user, $data['permissions']);

        # return user
        return $user;
    }
}
