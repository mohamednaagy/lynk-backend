<?php

namespace Tests\Traits;

use App\Enums\Area;
use RuntimeException;

trait InteractsWithApplication
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

    public function assertStatusCodeForAreaRoles($status, string $area, $request)
    {
        $areaKey = Area::getKey($area);

        $methodName = 'assert'.ucfirst($areaKey).'AreaUsersCannotAccess';

        if (! method_exists($this, $methodName)) {
            throw new RuntimeException("Method doesn't exist: $methodName");
        }

        $this->{$methodName}($status, $request);
    }

    public function assertSuperAdminAreaUsersCannotAccess($status, $request)
    {
        $roles = Area::roles(Area::SuperAdmin);

        foreach ($roles as $role) {
            $user = $this->createUser();
            $this->assignRoleToUser($user, $role);
            $request($user, $role)->assertStatus($status);
        }
    }

    public function assertLenderAreaUsersCannotAccess($status, $request)
    {
        $roles = Area::roles(Area::Lender);

        foreach ($roles as $role) {
            [$company] = $this->createCompanyByArea(Area::Lender);
            $user = $this->createLenderUser($company->id, $role);
            $request($user, $role)->assertStatus($status);
        }
    }

    public function assertTraderAreaUsersCannotAccess($status, $request)
    {
        $roles = Area::roles(Area::Trader);

        foreach ($roles as $role) {
            [$company] = $this->createCompanyByArea(Area::Trader);
            $user = $this->createLenderUser($company->id, $role);
            $request($user, $role)->assertStatus($status);
        }
    }

    public function assertStatusCodeToSpecificRoles(int $status, array $roles, $request)
    {
        foreach ($roles as $role) {
            if (in_array($role, Area::roles(Area::SuperAdmin))) {
                $admin = $this->createUser();
                $this->assignRoleToUser($admin, $role);
                $request($admin, $role)->assertStatus($status);

                continue;
            }

            [$company] = $this->createCompany();
            $user = $this->createLenderUser($company->id, $role);
            $request($user, $role)->assertStatus($status);
        }
    }
}
