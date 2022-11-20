<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static SuperAdmin()
 * @method static static Customer()
 */
final class Area extends Enum
{
    const SuperAdmin = 'SuperAdmin';

    const Customer = 'Customer';

    const Lender = 'Lender';

    // __REVIEW__ we need to be able to call "getRolesPerAreaMap" with Area::name  to return roles for certain area
    // Otherwise it will return all.
    // We need to adjust all places where this function is used
    // Hint: we can move each area roles to the files in Modules/Grantify/Support/Areas
    public static function getRolesPerAreaMap(): array
    {
        return [
            self::SuperAdmin => [
                Role::Admin,
                Role::Management,
            ],
            self::Lender => [
                Role::LenderAdmin,
                Role::LenderBilling,
                Role::LenderSupervisor,
                Role::LenderOrderCreator,
                Role::LenderApiUser,
            ],
        ];
    }
}
