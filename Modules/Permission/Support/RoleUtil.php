<?php

namespace Modules\Permission\Support;

use App\Enums\Role;
use Modules\Permission\Support\Roles\Admin;
use Modules\Permission\Support\Roles\Customer;

class RoleUtil
{
    public static array $roleMap = [
        Role::Admin => Admin::class,
        Role::Customer => Customer::class,
    ];

    /**
     * @param string $roleName
     * @return array
     */
    public static function getPermissionsForRole(string $roleName): array
    {
        $role = self::$roleMap[$roleName];

       return $role::$basePermissions;
    }

}
