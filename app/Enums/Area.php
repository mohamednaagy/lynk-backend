<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use Modules\Grantify\Support\Areas\SuperAdmin;

/**
 * @method static static SuperAdmin()
 * @method static static Customer()
 */
final class Area extends Enum
{
    const SuperAdmin = 'SuperAdmin';

    const Customer = 'Customer';

    const Lender = 'Lender';

    public static array $superAdminRoles = [
        Role::Admin,
        Role::Management,
    ];

    public static array $lenderRoles = [
        Role::LenderAdmin,
        Role::LenderBilling,
        Role::LenderSupervisor,
        Role::LenderOrderCreator,
        Role::LenderApiUser,
    ];

    public static function roles(string $area = null): array
    {
        return match ($area) {
            self::SuperAdmin => self::$superAdminRoles,
            self::Lender => self::$lenderRoles,
            default => [
                self::SuperAdmin => self::$superAdminRoles,
                self::Lender => self::$lenderRoles,
            ]
        };
    }
}
