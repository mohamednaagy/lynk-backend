<?php

namespace Modules\Grantify\Support;

use App\Enums\Role;
use Modules\Grantify\Support\Roles\Admin;
use Modules\Grantify\Support\Roles\Customer;
use Modules\Grantify\Support\Roles\LenderAdmin;
use Modules\Grantify\Support\Roles\LenderApiUser;
use Modules\Grantify\Support\Roles\LenderBilling;
use Modules\Grantify\Support\Roles\LenderOrderCreator;
use Modules\Grantify\Support\Roles\LenderSupervisor;

class RoleUtil
{
    public static array $roleMap = [
        Role::Admin => Admin::class,
        Role::Customer => Customer::class,
        Role::LenderAdmin => LenderAdmin::class,
        Role::LenderBilling => LenderBilling::class,
        Role::LenderOrderCreator => LenderOrderCreator::class,
        Role::LenderSupervisor => LenderSupervisor::class,
        Role::LenderApiUser => LenderApiUser::class,
    ];

    /**
     * @param  string  $roleName
     * @return array
     */
    public static function getPermissionsForRole(string $roleName): array|string
    {
        if (isset(self::$roleMap[$roleName])) {
            $role = self::$roleMap[$roleName];

            return $role::$basePermissions;
        }

        return [];
    }
}
