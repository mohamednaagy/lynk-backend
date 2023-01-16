<?php

namespace Tests\Traits;

use App\Enums\Role;
use App\Models\User;
use Modules\Grantify\Facades\Grantify;

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

    /**
     * Summary of createAdmin
     *
     * @param  string  $email
     * @param  array  $data
     * @return mixed
     */
    public function createAdmin(
        array $data = []
    ): User {
        $admin = $this->createUser($data);
        $this->assignRoleToUser($admin, Role::Admin);

        return $admin;
    }

    /**
     * Summary of createManager
     *
     * @param  array  $data
     * @param  string|array  $permissions
     * @return mixed
     */
    public function createManager(
        array $data = [],
        string|array $permissions = []
    ): User {
        $manager = $this->createUser($data);
        $this->assignRoleToUser($manager, Role::Manager);
        $this->assignPermissionToUser($manager, $permissions);

        return $manager;
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
            'password' => bcrypt('12345678'),
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
            'password' => bcrypt('12345678'),
            'company_id' => $companyId,
        ], $data));

        $this->assignRoleToUser($userLender, $role);

        return $userLender;
    }
}
