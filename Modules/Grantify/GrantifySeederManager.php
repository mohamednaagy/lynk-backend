<?php

namespace Modules\Grantify;

use App\Enums\Area;
use App\Enums\Role as EnumsRole;
use Illuminate\Support\Manager;
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
            foreach ($roles as $roleName) {
                if ($roleName !== 'General') {
                    $role = Role::where(['name' => $roleName, 'guard_name' => $defaultGuard])->first();
                    $allRoles[] = $role;
                }
                $permissions = RoleUtil::getPermissionsForRole($roleName);

                // If the permissions are only only star "*",
                // then that means this role has all permissions of their area which
                // which will be handled by the Gate::before in the AuthServiceProvider
                if ($permissions === '*') {
                    continue;
                }

                foreach ($permissions as $subject => $actions) {
                    foreach ($actions as $action) {
                        $permissionName = $area.'-'.$subject.'.'.$action;
                        $allPermissions[] = $permissionName;
                        foreach (config('grantify.guards') as $guard) {
                            $permission = Permission::findOrCreate($permissionName, $guard);

                            if ($guard === $defaultGuard && $roleName !== 'General') {
                                $role->givePermissionTo($permission);
                            }
                        }
                    }
                }
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
}
