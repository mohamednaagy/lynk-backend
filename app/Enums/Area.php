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

    public static function getRolesPerAreaMap(): array
    {
        return [
            self::SuperAdmin => [
                Role::Admin,
            ]
        ];
    }
}
