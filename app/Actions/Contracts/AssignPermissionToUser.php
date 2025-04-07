<?php

namespace App\Actions\Contracts;

use App\Models\User;
use Spatie\Permission\Models\Permission;

interface AssignPermissionToUser
{
    public function handle(User $user, string|array|Permission $permission): void;
}
