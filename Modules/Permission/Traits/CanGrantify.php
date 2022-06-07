<?php
namespace Modules\Permission\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\Permission\Exceptions\PermissionNotFoundException;
use Modules\Permission\Exceptions\RoleNotFoundException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait CanGrantify
{
    private function findRole(string $roleName, string $guardName = null): Model
    {
        $guardName = $guardName ?? config('auth.defaults.guard');
        $role = Role::query()
            ->where(['name' => $roleName, 'guard_name' => $guardName])
            ->first();

        if (!$role)
            throw new RoleNotFoundException();

        return $role;
    }

    private function findPermission(string $permissionName, string $guardName = null): Model
    {
        $guardName = $guardName ?? config('auth.defaults.guard');
        $permission = Permission::query()
            ->where(['name' => $permissionName, 'guard_name' => $guardName])
            ->first();

        if (!$permission)
            throw new PermissionNotFoundException();

        return $permission;
    }
}
