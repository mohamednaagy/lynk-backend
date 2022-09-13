<?php

namespace Modules\Permission\Facades;

use App\Models\User;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Facade;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

/**
 * @method static assignRoleToModel($user, string|array|Role $role)
 * @method static syncRoleToModel(User $user, string|array|Role $role)
 * @method static syncPermissionToModel(User $user, array $permissions)
 * @method static transformPermissionsToSubjectAction(Collection $permissions)
 * @method static assignPermissionToModel(User $user, string|array|Permission $permissions)
 * @method static transformPermissionsForMiddleware(string $area, string $subject, array $actions)
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
