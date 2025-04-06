<?php

namespace App\Actions\Contracts;

use App\Models\User;

interface CreateAdminWithRoleAndPermission
{
    public function __construct(
        CreateUser $createUser,
        AssignRoleToUser $assignRoleToUser,
        AssignPermissionToUser $assignPermissionToUser
    );

    /**
     * Create new user.
     */
    public function handle(array $data): User;
}
