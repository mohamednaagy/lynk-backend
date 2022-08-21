<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use Modules\Permission\Support\GeneralPermissionUtil;
use Modules\Permission\Support\RoleUtil;

/**
 * @method static static SuperAdmin()
 * @method static static Customer()
 */
final class Area extends Enum
{
    const SuperAdmin = 'SuperAdmin';
    const Customer = 'Customer';
    const General = 'General';

    public static function Roles()
    {
        return [
              self::SuperAdmin => [
                  RoleUtil::getPermissionsForRole(Role::Admin),
                  RoleUtil::getPermissionsForRole(Role::Customer),
                  GeneralPermissionUtil::getGeneralPermissionsForArea(self::SuperAdmin)
              ]
        ];
    }
}
