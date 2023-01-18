<?php

namespace Tests\Traits;

use App\Enums\Area;
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

    public function assertStatusCodeForAreaRoles($status, string $area, $request)
    {
        $areaKey = Area::getKey($area);

        $methodName = 'assertStatusFor'.ucfirst($areaKey).'AreaUsers';

        if (! method_exists($this, $methodName)) {
            throw new RuntimeException("Method doesn't exist: $methodName");
        }

        $this->{$methodName}($status, $request);
    }

    public function assertStatusForSuperAdminAreaUsers($status, $request)
    {
        $roles = Area::roles(Area::SuperAdmin);

        foreach ($roles as $role) {
            $user = $this->createSuperAdminUser($role);
            dump($user->id);
            $request($user, $role)->assertStatus($status);
        }
    }

    public function assertStatusForLenderAreaUsers($status, $request)
    {
        $roles = Area::roles(Area::Lender);

        foreach ($roles as $role) {
            [$company] = $this->createCompanyByArea(Area::Lender);
            $user = $this->createLenderUser($company->id, $role);
            $request($user, $role)->assertStatus($status);
        }
    }

    public function assertStatusForTraderAreaUsers($status, $request)
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
            $area = Area::getAreaByRole($role);
            switch ($area) {
                case Area::SuperAdmin:
                    $admin = $this->createUserByRole($role);
                    $request($admin, $role)->assertStatus($status);

                    break;

                case Area::Customer:
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
