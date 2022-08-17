<?php

namespace App\Actions\Contracts;

use App\Models\User;
use Spatie\Permission\Models\Permission;

interface SyncPermissionToUser
{
    /**
     * @param User $user
     * @param string|array|Permission $permission
     * @return void
     */
    public function __invoke(User $user, string|array|Permission $permission): void;
}
