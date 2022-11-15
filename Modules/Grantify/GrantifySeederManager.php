<?php

namespace Modules\Grantify;

use App\Enums\Area;
use App\Enums\Role as EnumsRole;
use Illuminate\Support\Manager;
use Modules\Grantify\Support\AreaUtil;
use Modules\Grantify\Support\RoleUtil;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantifySeederManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        //
    }

    /**
     * Seed All Roles into DB
     *
     * @return void
     */
    public function seedRoles(): void
    {
        foreach (EnumsRole::asArray() as $role) {
            foreach (config('grantify.guards') as $guard) {
                Role::findOrCreate($role, $guard);
            }
        }
    }

    /**
     * Seed All Permissions into DB
     *
     * @param  bool  $withSync
     * @return void
     */
    public function seedPermissions(bool $withSync = false): void
    {
        $allPermissions = [];
        $allRoles = [];
        $defaultGuard = config('grantify.default_guard');
        foreach (Area::getRolesPerAreaMap() as $area => $roles) {
            // seed area class permissions (direct permissions)
            $areaPermissions = AreaUtil::getAreaPermissions($area);
            $storedPermissions = $this->storePermissions($areaPermissions, $area, $defaultGuard);
            $allPermissions = array_merge($allPermissions, $storedPermissions);

            // seed permissions of each role
            foreach ($roles as $roleName) {
                if ($roleName !== 'General') {
                    $role = Role::where(['name' => $roleName, 'guard_name' => $defaultGuard])->first();
                    $allRoles[] = $role;
                }
                $permissions = RoleUtil::getPermissionsForRole($roleName);

                // If the permissions are only star "*",
                // then that means this role has all permissions of their area
                // which will be handled by the Gate::before in the AuthServiceProvider
                if ($permissions === '*') {
                    continue;
                }

                $rolePermissions = $this->storePermissions($permissions, $area, $defaultGuard, $role);
                $allPermissions = array_merge($allPermissions, $rolePermissions);
            }
        }

        if ($withSync) {
            $databasePermissions = Permission::all()->pluck('name')->toArray();
            $removedPermissions = array_diff($databasePermissions, $allPermissions);

            $permissions = Permission::query()
                ->whereIn('name', $removedPermissions)
                ->where('guard_name', $defaultGuard)
                ->get();

            foreach ($allRoles as $role) {
                $role->revokePermissionTo($permissions);
            }
        }
    }

    public function storePermissions(
        array $permissions,
        string $area,
        string $defaultGuard,
        Role $role = null
    ): array {
        $storedPermissions = [];
        foreach ($permissions as $subject => $actions) {
            foreach ($actions as $action) {
                $permissionName = $area.'-'.$subject.'.'.$action;
                $storedPermissions[] = $permissionName;
                foreach (config('grantify.guards') as $guard) {
                    $permission = Permission::findOrCreate($permissionName, $guard);

                    if ($role && $guard === $defaultGuard) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        }

        return  $storedPermissions;
    }
}
