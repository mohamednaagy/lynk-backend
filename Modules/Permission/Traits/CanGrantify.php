<?php
namespace Modules\Permission\Traits;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Modules\Permission\Exceptions\RoleNotFoundException;
use Modules\Permission\Exceptions\PermissionNotFoundException;

trait CanGrantify
{
    /**
     * @throws RoleNotFoundException
     */
    private function findRole(string $roleName, string $guardName = null): Model
    {
        $guardName = $guardName ?? config('permission.default_guard');
        $role = Role::query()
            ->where(['name' => $roleName, 'guard_name' => $guardName])
            ->first();

        if (!$role)
            throw new RoleNotFoundException();

        return $role;
    }

    /**
     * @throws PermissionNotFoundException
     */
    private function findPermission(string $permissionName, string $guardName = null): Model
    {
        $guardName = $guardName ?? config('permission.default_guard');
        $permission = Permission::query()
            ->where(['name' => $permissionName, 'guard_name' => $guardName])
            ->first();

        if (!$permission)
            throw new PermissionNotFoundException();

        return $permission;
    }
}
