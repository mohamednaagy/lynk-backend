<?php

namespace Modules\Permission\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static assignRoleToModel($user, mixed $role)
 * @method static assignPermissionToModel(\App\Models\User $user, mixed $permissions)
 * @method static syncRoleToModel(\App\Models\User $user, mixed $role)
 * @method static syncPermissionToModel(\App\Models\User $user, mixed $permissions)
 */
class Grantify extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'grantify';
    }

}
