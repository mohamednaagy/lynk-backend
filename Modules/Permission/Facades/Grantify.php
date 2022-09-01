<?php

namespace Modules\Permission\Facades;

use App\Models\User;
use RuntimeException;
use Illuminate\Support\Facades\Facade;

/**
 * @method static assignRoleToModel($user, mixed $role)
 * @method static assignPermissionToModel(User $user, mixed $permissions)
 * @method static syncRoleToModel(User $user, mixed $role)
 * @method static syncPermissionToModel(User $user, mixed $permissions)
 * @method static getAuthUserPermissionsForMiddleware()
 */
class Grantify extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected static function getFacadeAccessor(): string
    {
        return 'grantify';
    }

}
