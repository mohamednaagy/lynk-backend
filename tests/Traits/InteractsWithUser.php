<?php

namespace Tests\Traits;

use App\Enums\Area;
use App\Enums\Role;
use App\Models\User;
use Modules\Grantify\Facades\Grantify;
use RuntimeException;

trait InteractsWithUser
{
    public function createUser(array $data = [])
    {
        return User::factory()->create($data);
    }

    public function assignRoleToUser(User $user, string $role)
    {
        return Grantify::assignRoleToModel($user, $role);
    }

    public function assignPermissionToUser(User $user, string|array $permissions = [])
    {
        $permissions = (array) $permissions;
        foreach ($permissions as $permission) {
            Grantify::assignPermissionToModel($user, $permission);
        }

        return $user;
    }

    public function createUserByRole($role, $companyId = null, $data = [])
    {
        $areaKey = Area::getAreaByRole($role);
        $methodName = 'create'.ucfirst($areaKey).'User';

        if (! method_exists($this, $methodName)) {
            throw new RuntimeException("Method doesn't exist: $methodName");
        }

        if ($areaKey == Area::SuperAdmin) {
            return $this->{$methodName}($role, $data);
        }

        if (is_null($companyId)) {
            throw new RuntimeException("CompanyID can't be null");
        }

        return $this->{$methodName}($companyId, $role, $data);
    }

    /**
     * Summary of createAdmin
     *
     * @param  string  $email
     * @param  array  $data
     * @return mixed
     */
    public function createSuperAdminUser(
        $role = Role::Admin,
        array $data = []
    ): User {
        $admin = $this->createUser($data);
        $this->assignRoleToUser($admin, $role);

        return $admin;
    }

    /**
     * @param  int  $companyId
     * @param  string  $role
     * @param  array  $data
     * @return User
     */
    public function createLenderUser(
        int $companyId,
        string $role = Role::LenderAdmin,
        array $data = []
    ): User {
        $userLender = $this->createUser(array_merge([
            'company_id' => $companyId,
        ], $data));

        $this->assignRoleToUser($userLender, $role);

        return $userLender;
    }

    /**
     * @param  int  $companyId
     * @param  string  $role
     * @param  array  $data
     * @return User
     */
    public function createTraderUser(
        int $companyId,
        string $role = Role::TraderAdmin,
        array $data = []
    ): User {
        $userLender = $this->createUser(array_merge([
            'company_id' => $companyId,
        ], $data));

        $this->assignRoleToUser($userLender, $role);

        return $userLender;
    }

    /**
     * @param  string  $role
     * @param  array  $data
     * @return User
     */
    public function createCustomerUser(
        string $role = Role::Customer,
        array $data = []
    ): User {
        $userCustomer = $this->createUser($data);

        $this->assignRoleToUser($userCustomer, $role);

        return $userCustomer;
    }
}
