<?php

namespace Modules\Permission;

use Illuminate\Support\Manager;
use Modules\Permission\Contracts\Grantifiable;
use Modules\Permission\Exceptions\PermissionNotFoundException;
use Modules\Permission\Exceptions\RoleNotFoundException;
use Modules\Permission\Traits\CanGrantify;

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
     * @param string $roleName
     * @param string|null $guardName
     * @return array
     * @throws RoleNotFoundException
     */
    public function getRolePermissions(string $roleName, string $guardName = null): array
    {
        $role = $this->findRole($roleName, $guardName);
        return $role->permissions->toArray();
    }

    /**
     * Get All Permissions for Model
     *
     * @param Grantifiable $grantifiable
     * @return array
     */
    public function getAllPermissionsForModel(Grantifiable $grantifiable): array
    {
        return $grantifiable->getAllPermissions()->toArray();
    }

    /**
     * Get All Permissions In Subject Action Format
     *
     * @param array $permissions
     * @return array
     */
    public function getAllPermissionsInSubjectAction(array $permissions): array
    {
        $permissionsSubjectAction = [];

        foreach ($permissions as $permission) {
            $subjectAction = explode('.', $permission['name']);

            if (array_key_exists(current($subjectAction), $permissionsSubjectAction))
                array_push($permissionsSubjectAction[current($subjectAction)], end($subjectAction));
            else
                $permissionsSubjectAction[current($subjectAction)] = array(end($subjectAction));
        }

        return $permissionsSubjectAction;
    }

    /**
     * Assign a permission to a role.
     *
     * @param string $roleName
     * @param string $permissionName
     * @param string|null $guardName
     * @return void
     * @throws PermissionNotFoundException
     * @throws RoleNotFoundException
     */
    public function assignPermissionToRole(string $roleName, string $permissionName, string $guardName = null): void
    {
        $role = $this->findRole($roleName, $guardName);
        $permission = $this->findPermission($permissionName, $guardName);
        $role->givePermissionTo($permission);
    }

    /**
     * Remove a permission that is assigned to a role.
     *
     * @param string $roleName
     * @param string $permissionName
     * @param string|null $guardName
     * @return void
     * @throws PermissionNotFoundException
     * @throws RoleNotFoundException
     */
    public function removePermissionFromRole(string $roleName, string $permissionName, string $guardName = null): void
    {
        $role = $this->findRole($roleName, $guardName);
        $permission = $this->findPermission($permissionName, $guardName);
        $role->revokePermissionTo($permission);
    }

}
