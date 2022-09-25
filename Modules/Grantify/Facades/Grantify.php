<?php

namespace Modules\Grantify\Facades;

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
 * @method static transformSubjectActionToPermissionName(array[] $permission)
 * @method static transformPermissionsToSubjectAction(Collection $permissions)
 * @method static assignPermissionToModel(User $user, string|array|Permission $permissions)
 * @method static transformToPermissionsFormat(string $area, string $subject, array $actions, bool $forMiddleware = true)
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
