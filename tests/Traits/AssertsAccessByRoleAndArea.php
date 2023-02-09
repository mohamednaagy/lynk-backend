<?php

namespace Tests\Traits;

use App\Enums\Area;
use Modules\Grantify\Support\RoleUtil;
use RuntimeException;

trait AssertsAccessByRoleAndArea
{
    use InteractsWithCompany;
    use InteractsWithUser;

    public function assertStatusCodeForAllRolesExceptForArea($status, array $exceptedArea, $request)
    {
        $areas = Area::asArray();
        foreach ($areas as $area) {
            if (! in_array($area, $exceptedArea)) {
                $this->assertStatusCodeForAreaRoles($status, $area, $request);
            }
        }
    }

    public function assertStatusCodeExceptForPermissions($status, array $exceptedPermissions, $request)
    {
        $excludedPermissions = [];

        foreach ($exceptedPermissions as $area => $permission) {
            if (is_string($permission)) {
                $exceptedPermissions[] = $permission;
            }
            if (is_array($permission)) {
                $exceptedPermissions = array_merge(perm_arr($area, $permission), $exceptedPermissions);
            }
        }

        $areas = Area::asArray();
        foreach ($areas as $area) {
            $roles = Area::roles($area);
            foreach ($roles as $role) {
                $permissions = RoleUtil::getPermissionsForRole($role);
                foreach ($permissions as $subject => $actions) {
                    foreach ($actions as $action) {
                        $permission = perm($area, [$subject, $action]);
                        if (in_array($permission, $excludedPermissions)) {
                            continue;
                        }
                        $this->assertStatusCodeForAreaRolesAndPermissions($status, $area, [$permission], $request);
                    }
                }
            }
        }
    }

    public function assertStatusCodeForAreaRoles($status, string $area, $request)
    {
        $areaKey = Area::getKey($area);

        $methodName = 'assertStatusFor'.ucfirst($areaKey).'AreaUsers';

        if (! method_exists($this, $methodName)) {
            throw new RuntimeException("Method doesn't exist: $methodName");
        }

        $this->{$methodName}($status, $request);
    }

    public function assertStatusCodeForAreaRolesAndPermissions($status, string $area, array $permissions, $request)
    {
        $areaKey = Area::getKey($area);

        $methodName = 'assertStatusFor'.ucfirst($areaKey).'AreaUsers';

        if (! method_exists($this, $methodName)) {
            throw new RuntimeException("Method doesn't exist: $methodName");
        }

        $this->{$methodName}($status, $request, $permissions);
    }

    public function assertStatusForSuperAdminAreaUsers($status, $request, $permissions = [])
    {
        $roles = Area::roles(Area::SuperAdmin);

        foreach ($roles as $role) {
            $user = $this->createUser();
            $this->assignRoleToUser($user, $role);
            $this->assignPermissionToUser($user, $permissions);
            $request($user, $role, $permissions)->assertStatus($status);
        }
    }

    public function assertStatusForLenderAreaUsers($status, $request, $permissions = [])
    {
        $roles = Area::roles(Area::Lender);

        foreach ($roles as $role) {
            [$company] = $this->createCompanyByArea(Area::Lender);
            $user = $this->createLenderUser($company->id, $role);
            $this->assignPermissionToUser($user, $permissions);
            $request($user, $role, $permissions)->assertStatus($status);
        }
    }

    public function assertStatusForTraderAreaUsers($status, $request, $permissions = [])
    {
        $roles = Area::roles(Area::Trader);

        foreach ($roles as $role) {
            [$company] = $this->createCompanyByArea(Area::Trader);
            $user = $this->createLenderUser($company->id, $role);
            $this->assignPermissionToUser($user, $permissions);
            $request($user, $role, $permissions)->assertStatus($status);
        }
    }

    public function assertStatusCodeToSpecificRoles(int $status, array $roles, $request)
    {
        foreach ($roles as $role) {
            $area = Area::getAreaByRole($role);
            switch ($area) {
                case Area::SuperAdmin:
                    $admin = $this->createUserByRole($role);
                    $request($admin, $role)->assertStatus($status);

                    break;

                default:
                    [$company] = $this->createCompanyByArea($area);
                    $user = $this->createUserByRole($role, $company->id);
                    $request($user, $role)->assertStatus($status);

                    break;
            }
        }
    }
}
