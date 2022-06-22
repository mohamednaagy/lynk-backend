<?php

namespace Modules\Permission;

use Illuminate\Support\Manager;
use Modules\Permission\Enums\Area;
use Modules\Permission\Enums\Role as EnumsRole;
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
            foreach (config('permission.guards') as $guard) {
                Role::findOrCreate($role, $guard);
            }
        }
    }

    /**
     * Seed All Permissions into DB
     *
     * @param bool $withSync
     * @return void
     */
    public function seedPermissions(bool $withSync = false): void
    {
        $allPermissions = [];
        foreach (Area::Roles() as $area => $roles) {
            foreach ($roles as $hasPermissions) {
                foreach ($hasPermissions as $subject => $actions) {
                    foreach ($actions as $action) {
                        $permissionName = $area . '-' . $subject . '.' . $action;
                        $allPermissions[] = $permissionName;
                        foreach (config('permission.guards') as $guard) {
                            Permission::findOrCreate($permissionName, $guard);
                        }
                    }
                }
            }
        }

        if ($withSync) {
            $databasePermissions = Permission::all()->pluck('name')->toArray();
            $removedPermissions = array_diff($databasePermissions, $allPermissions);

            Permission::query()->whereIn('name', $removedPermissions)->delete();
        }
    }

}
