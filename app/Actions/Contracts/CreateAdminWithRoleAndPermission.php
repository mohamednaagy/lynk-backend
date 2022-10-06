<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface CreateAdminWithRoleAndPermission
{
    /**
     * @param  CreateUser  $createUser
     * @param  AssignRoleToUser  $assignRoleToUser
     * @param  AssignPermissionToUser  $assignPermissionToUser
     */
    public function __construct(
        CreateUser $createUser,
        AssignRoleToUser $assignRoleToUser,
        AssignPermissionToUser $assignPermissionToUser
    );

    /**
     * Create new user.
     *
     * @param  array  $data
     * @return User
     */
    public function handle(array $data): User;
}
