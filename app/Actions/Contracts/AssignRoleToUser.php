<?php

namespace App\Actions\Contracts;

use App\Models\User;
use Spatie\Permission\Models\Role;

interface AssignRoleToUser
{
    /**
     * @param  User  $user
     * @param  string|array|Role  $role
     * @return void
     */
    public function handle(User $user, string|array|Role $role): void;
}
