<?php

namespace Modules\Grantify\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\Grantify\Exceptions\PermissionNotFoundException;
use Modules\Grantify\Exceptions\RoleNotFoundException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

        if (! $role) {
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

        if (! $permission) {
            throw new PermissionNotFoundException();
        }

        return $permission;
    }
}
