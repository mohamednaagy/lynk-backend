<?php

namespace App\Actions\Contracts\Lenders;

use App\Actions\Contracts\AssignPermissionToUser;
use App\Actions\Contracts\AssignRoleToUser;
use App\Actions\Contracts\CreateUser;
use App\Models\User;

interface CreateLenderUserWithRoleAndPermission
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
