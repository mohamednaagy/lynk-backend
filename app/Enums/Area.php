<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use Modules\Grantify\Support\RoleUtil;
use Modules\Grantify\Support\GeneralPermissionUtil;

/**
 * @method static static SuperAdmin()
 * @method static static Customer()
 */
final class Area extends Enum
{
    const SuperAdmin = 'SuperAdmin';
    const Customer = 'Customer';
    const General = 'General';

    public static function getRolesPerAreaMap(): array
    {
        return [
              self::SuperAdmin => [
                  Role::Admin,
              ]
        ];
    }
}
