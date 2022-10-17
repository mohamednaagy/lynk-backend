<?php

namespace App\Actions\Contracts\Lenders;

use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\CreateUser;
use App\Models\User;

interface CreateLenderUserWithRoleAndPermission
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
