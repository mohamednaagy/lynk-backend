<?php

namespace Modules\Grantify\Support;

use App\Enums\Role;
use Modules\Grantify\Support\Roles\Admin;
use Modules\Grantify\Support\Roles\ApiAdmin;
use Modules\Grantify\Support\Roles\LenderAdmin;
use Modules\Grantify\Support\Roles\LenderApiUser;
use Modules\Grantify\Support\Roles\LenderBilling;
use Modules\Grantify\Support\Roles\LenderOrderCreator;
use Modules\Grantify\Support\Roles\LenderSupervisor;
use Modules\Grantify\Support\Roles\Manager;
use Modules\Grantify\Support\Roles\TraderAdmin;

class RoleUtil
{
    public static array $roleMap = [
        Role::Admin => Admin::class,
        Role::Manager => Manager::class,
        Role::LenderAdmin => LenderAdmin::class,
        Role::ApiAdmin => ApiAdmin::class,
        Role::LenderBilling => LenderBilling::class,
        Role::LenderOrderCreator => LenderOrderCreator::class,
        Role::LenderSupervisor => LenderSupervisor::class,
        Role::LenderApiUser => LenderApiUser::class,
        Role::TraderAdmin => TraderAdmin::class,
    ];

    /**
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
