<?php
namespace Modules\Grantify\Traits;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Modules\Grantify\Exceptions\RoleNotFoundException;
use Modules\Grantify\Exceptions\PermissionNotFoundException;

trait CanGrantify
{
    /**
     * @throws RoleNotFoundException
     */
    private function findRole(string $roleName, string $guardName = null): Model
    {
        $guardName = $guardName ?? config('grantify.default_guard');
        $role = Role::query()
            ->where(['name' => $roleName, 'guard_name' => $guardName])
            ->first();

        if (!$role) {
            throw new RoleNotFoundException();
        }

        return $role;
    }

    /**
     * @throws PermissionNotFoundException
     */
    private function findPermission(string $permissionName, string $guardName = null): Model
    {
        $guardName = $guardName ?? config('grantify.default_guard');
        $permission = Permission::query()
            ->where(['name' => $permissionName, 'guard_name' => $guardName])
            ->first();

        if (!$permission) {
            throw new PermissionNotFoundException();
        }

        return $permission;
    }
}
