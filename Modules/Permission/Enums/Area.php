<?php

namespace Modules\Permission\Enums;

use BenSampo\Enum\Enum;
use Modules\Permission\Support\RoleUtil;

/**
 * @method static static SuperAdmin()
 * @method static static Customer()
 */
final class Area extends Enum
{
    const SuperAdmin = 'SuperAdmin';
    const Customer = 'Customer';

    public static function Roles()
    {
        return [
              self::SuperAdmin => [
                  RoleUtil::$roleMap[Role::Admin]::$basePermissions,
                  RoleUtil::$roleMap[Role::Customer]::$basePermissions
              ]
        ];
    }
}
