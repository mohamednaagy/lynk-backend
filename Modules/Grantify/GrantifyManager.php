<?php

namespace Modules\Grantify;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Manager;
use Modules\Grantify\Contracts\Grantifiable;
use Modules\Grantify\Exceptions\PermissionNotFoundException;
use Modules\Grantify\Exceptions\RoleNotFoundException;
use Modules\Grantify\Traits\CanGrantify;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GrantifyManager extends Manager
{
    use CanGrantify;

    public function getDefaultDriver()
    {
        //
    }

    /**
     * Get Permissions by Area.
     *
     * @param  string  $area
     * @param  string|null  $guardName
     * @return Collection
     */
    public function getPermissionsByArea(string $area, string $guardName = null): Collection
    {
        $area = $area.'-';
        $guardName = $guardName ?? config('grantify.default_guard');

        return Permission::query()->where('name', 'LIKE', $area.'%')
            ->where('guard_name', $guardName)
            ->get();
    }

    /**
     * get Role Permissions
     *
     * @param  string|Role  $role
     * @param  string|null  $guardName
     * @return array
     *
     * @throws RoleNotFoundException
     */
    public function getRolePermissions(Role|string $role, string $guardName = null): array
    {
        if (! $role instanceof Role) {
            $role = $this->findRole($role, $guardName);
        }

        return $role->permissions->toArray();
    }

    /**
     * Get All Permissions for Model
     *
     * @param  Grantifiable  $grantifiable
     * @return Collection
     */
    public function getAllPermissionsForModel(Grantifiable $grantifiable): Collection
    {
        return $grantifiable->getAllPermissions();
    }

    /**
     * Get All Permissions In Subject Action Format
     *
     * @param  Collection  $permissions
     * @return array
     */
    public function transformPermissionsToSubjectAction(Collection $permissions): array
    {
        $permissionsInSubjectAction = [];

        foreach ($permissions as $permission) {
            $subjectAction = explode('.', $permission->name ?? $permission);

            $permissionsInSubjectAction[] = [
                'subject' => $subjectAction[0],
                'action' => $subjectAction[1],
            ];
        }

        return $permissionsInSubjectAction;
    }

    /**
     * Assign a permission to a role.
     *
     * @param  string|Role  $role
     * @param  string|Permission  $permission
     * @param  string|null  $guardName
     * @return void
     *
     * @throws PermissionNotFoundException
     * @throws RoleNotFoundException
     */
    public function assignPermissionToRole(Role|string $role, string|Permission $permission, string $guardName = null): void
    {
        if (! $role instanceof Role) {
            $role = $this->findRole($role, $guardName);
        }

        if (! $permission instanceof Permission) {
            $permission = $this->findPermission($permission, $guardName);
        }

        $role->givePermissionTo($permission);
    }

    /**
     * Remove a permission that is assigned to a role.
     *
     * @param  string|Role  $role
     * @param  string|Permission  $permission
     * @param  string|null  $guardName
     * @return void
     *
     * @throws PermissionNotFoundException
     * @throws RoleNotFoundException
     */
    public function removePermissionFromRole(Role|string $role, string|Permission $permission, string $guardName = null): void
    {
        if (! $role instanceof Role) {
            $role = $this->findRole($role, $guardName);
        }

        if (! $permission instanceof Permission) {
            $permission = $this->findPermission($permission, $guardName);
        }

        $role->revokePermissionTo($permission);
    }

    /**
     * Assign a direct permission to a model.
     * The permission argument could be in type of string or Permission object or array of subject action format
     *
     * @param  Grantifiable  $grantifiable
     * @param  string|array|Permission  $permission
     * @param  string|null  $guardName
     * @return void
     *
     * @throws PermissionNotFoundException
     */
    public function assignPermissionToModel(Grantifiable $grantifiable, string|array|Permission $permission, string $guardName = null): void
    {
        if (is_string($permission)) {
            $permission = $this->findPermission($permission, $guardName);
        } elseif (is_array($permission)) {
            $permission = $this->transformSubjectActionToPermissionName($permission);
        }

        $grantifiable->givePermissionTo($permission);
    }

    /**
     * Assign roles to a model.
     * The role argument could be in type of string or Role object or array of roles
     *
     * @param  Grantifiable  $grantifiable
     * @param  string|array|Role  $role
     * @param  string|null  $guardName
     * @return void
     *
     * @throws RoleNotFoundException
     */
    public function assignRoleToModel(Grantifiable $grantifiable, string|array|Role $role, string $guardName = null): void
    {
        if (is_string($role)) {
            $role = $this->findRole($role, $guardName);
        }

        $grantifiable->assignRole($role);
    }

    /**
     * Sync direct permissions to a model.
     *
     * @param  Grantifiable  $grantifiable
     * @param  array  $permissions
     * @return void
     */
    public function syncPermissionToModel(Grantifiable $grantifiable, array $permissions): void
    {
        $permission = $this->transformSubjectActionToPermissionName($permissions);
        $grantifiable->syncPermissions($permission);
    }

    /**
     * Sync roles to a model.
     *
     * @param  Grantifiable  $grantifiable
     * @param  string|array|Role  $role
     * @return void
     */
    public function syncRoleToModel(Grantifiable $grantifiable, ...$role): void
    {
        $grantifiable->syncRoles($role);
    }

    /**
     * Transform TO Permissions Format (Area-Subject.Action).
     *
     * @param  string  $area
     * @param  string  $subject
     * @param  array  $actions
     * @param  bool  $forMiddleware
     * @return string|array
     */
    public function transformToPermissionsFormat(
        string $area,
        string $subject,
        array $actions,
        bool $forMiddleware = true
    ): string|array {
        $permissionChain = '';

        foreach ($actions as $key => $action) {
            $permission = $area.'-'.$subject.'.'.$action;
            $permissionChain .= $permission;

            if ($key == array_key_last($actions)) {
                break;
            }

            $permissionChain .= '|';
        }

        if (! $forMiddleware) {
            $permissionChain = explode('|', $permissionChain);
        }

        return $permissionChain;
    }

    /**
     * @param  array  $permissions
     * @return array
     */
    public function transformSubjectActionToPermissionName(array $permissions): array
    {
        $permissionsList = [];

        foreach ($permissions as $key => $permission) {
            $permissionName = $permission['subject'].'.';

            foreach ($permission['actions'] as $action) {
                $permissionsList[] = $permissionName.$action;
            }
        }

        return $permissionsList;
    }

    public function transformToAreaSubject(string $area, array $permissions): array
    {
        $transformedPermissions = collect($permissions)->map(function ($permission) use ($area) {
            $permission['subject'] = $area.'-'.$permission['subject'];

            return $permission;
        });

        return $transformedPermissions->toArray();
    }
}
