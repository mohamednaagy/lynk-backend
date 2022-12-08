<?php

namespace Tests\Traits;

use App\Enums\Role;
use App\Models\User;
use Modules\Grantify\Facades\Grantify;

trait InteractsWithAdmin
{
    /**
     * @param  int  $companyId
     * @param  string  $role
     * @param  string  $email
     * @param  array  $data
     * @return Collection|Model|mixed
     */
    public function createAdmin(
        string $email = 'admin@bim.com',
        array $data = []
    ): mixed {
        $admin = User::factory()->create(array_merge([
            'email' => $email,
            'password' => bcrypt('12345678'),
        ], $data));

        Grantify::assignRoleToModel($admin, Role::Admin);

        return $admin;
    }

    /**
     * Summary of createManager
     *
     * @param  string  $email
     * @param  array  $data
     * @param  string|array  $permissions
     * @return mixed
     */
    public function createManager(
        string $email = 'Manager@bim.com',
        array $data = [],
        string|array $permissions = []
    ): mixed {
        $manager = User::factory()->create(array_merge([
            'email' => $email,
            'password' => bcrypt('12345678'),
        ], $data));

        Grantify::assignRoleToModel($manager, Role::Manager);

        if (! empty($permissions)) {
            Grantify::assignPermissionToModel($manager, $permissions);
        }

        return $manager;
    }
}
