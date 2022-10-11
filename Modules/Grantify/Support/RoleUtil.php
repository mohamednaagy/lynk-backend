<?php

namespace Modules\Grantify\Support;

use App\Enums\Role;
use Modules\Grantify\Support\Roles\Admin;
use Modules\Grantify\Support\Roles\Customer;
use Modules\Grantify\Support\Roles\LenderAdmin;

class RoleUtil
{
    public static array $roleMap = [
        Role::Admin => Admin::class,
        Role::Customer => Customer::class,
        Role::LenderAdmin => LenderAdmin::class,
    ];

    /**
     * @param  string  $roleName
     * @return array
     */
    public static function getPermissionsForRole(string $roleName): array|string
    {
        $role = self::$roleMap[$roleName];

        return $role::$basePermissions;
    }
}
