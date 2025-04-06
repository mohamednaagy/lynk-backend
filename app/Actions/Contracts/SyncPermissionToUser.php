<?php

namespace App\Actions\Contracts;

use App\Models\User;
use Spatie\Permission\Models\Permission;

interface SyncPermissionToUser
{
    public function handle(User $user, string|array|Permission $permission): void;
}
