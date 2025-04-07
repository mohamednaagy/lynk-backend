<?php

namespace App\Actions\Contracts;

use App\Models\User;
use Spatie\Permission\Models\Role;

interface AssignRoleToUser
{
    public function handle(User $user, string|array|Role $role): void;
}
