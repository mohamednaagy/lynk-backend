<?php

namespace Modules\Permission\Drivers;

use Modules\Permission\Contracts\Grantifiable;
use Modules\Permission\Contracts\GrantifyDriverInterface;
use Spatie\Permission\Models\Role;

class SpatieDriver implements GrantifyDriverInterface
{
    /**
     * getRolePermissions
     *
     * @param  string $roleName
     * @return array
     */
    public function getRolePermissions(string $roleName): array
    {
        $role = Role::where(['name' => $roleName, 'guard_name' => 'web'])->first();
        return $role->permissions->pluck('name')->toArray();
    }

    public function getGrantifiablePermissions(Grantifiable $grantifiable): array
    {
        $roles_permissions = $grantifiable->roles->transform(function ($role) {
            $roles[$role->name] = $role->permissions->pluck('name')->toArray();
            return $roles;
        })->toArray();

        if (!empty($roles_permissions)) {
            return $roles_permissions[0];
        }

        return [];
    }
}
