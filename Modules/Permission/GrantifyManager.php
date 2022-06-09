<?php

namespace Modules\Permission;

use Illuminate\Support\Collection;
use Illuminate\Support\Manager;
use Modules\Permission\Contracts\Grantifiable;
use Modules\Permission\Exceptions\PermissionNotFoundException;
use Modules\Permission\Exceptions\RoleNotFoundException;
use Modules\Permission\Traits\CanGrantify;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantifyManager extends Manager
{
    use CanGrantify;

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
     * get Role Permissions
     *
     * @param string|Role $role
     * @param string|null $guardName
     * @return array
     * @throws RoleNotFoundException
     */
    public function getRolePermissions(Role|string $role, string $guardName = null): array
    {
        if (!$role instanceof Role)
            $role = $this->findRole($role, $guardName);

        return $role->permissions->toArray();
    }

    /**
     * Get All Permissions for Model
     *
     * @param Grantifiable $grantifiable
     * @return Collection
     */
    public function getAllPermissionsForModel(Grantifiable $grantifiable): Collection
    {
        return $grantifiable->getAllPermissions();
    }

    /**
     * Get All Permissions In Subject Action Format
     *
     * @param Collection $permissions
     * @return array
     */
    public function transformPermissionsToSubjectAction(Collection $permissions): array
    {
        $permissionsInSubjectAction = [];

        foreach ($permissions as $permission) {
            $subjectAction = explode('.', $permission->name);

            if (array_key_exists(current($subjectAction), $permissionsInSubjectAction))
                $permissionsInSubjectAction[current($subjectAction)][] = end($subjectAction);
            else
                $permissionsInSubjectAction[current($subjectAction)] = [end($subjectAction)];
        }

        return $permissionsInSubjectAction;
    }

    /**
     * Assign a permission to a role.
     *
     * @param string|Role $role
     * @param string|Permission $permission
     * @param string|null $guardName
     * @return void
     * @throws PermissionNotFoundException
     * @throws RoleNotFoundException
     */
    public function assignPermissionToRole(Role|string $role, string|Permission $permission, string $guardName = null): void
    {
        if (!$role instanceof Role)
            $role = $this->findRole($role, $guardName);

        if (!$permission instanceof Permission)
            $permission = $this->findPermission($permission, $guardName);

        $role->givePermissionTo($permission);
    }

    /**
     * Remove a permission that is assigned to a role.
     *
     * @param string|Role $role
     * @param string|Permission $permission
     * @param string|null $guardName
     * @return void
     * @throws PermissionNotFoundException
     * @throws RoleNotFoundException
     */
    public function removePermissionFromRole(Role|string $role, string|Permission $permission, string $guardName = null): void
    {
        if (!$role instanceof Role)
            $role = $this->findRole($role, $guardName);

        if (!$permission instanceof Permission)
            $permission = $this->findPermission($permission, $guardName);

        $role->revokePermissionTo($permission);
    }

    /**
     * Assign a direct permission to a model.
     * The permission argument could be in type of string or Permission object or array of subject action format
     *
     * @param Grantifiable $grantifiable
     * @param string|array|Permission $permission
     * @param string|null $guardName
     * @return void
     */
    public function assignPermissionToModel(Grantifiable $grantifiable, string|array|Permission $permission, string $guardName = null): void
    {
        if (is_array($permission))
            $permission = $this->transformSubjectActionToPermissionName($permission);

        $grantifiable->givePermissionTo($permission);
    }

}
