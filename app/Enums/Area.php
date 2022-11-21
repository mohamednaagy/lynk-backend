<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use Modules\Grantify\Support\Areas\Lender;
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

    public static function roles(string $area = null): array
    {
        return match ($area) {
            self::SuperAdmin => SuperAdmin::$roles,
            self::Lender => Lender::$roles,
            default => [
                self::SuperAdmin => SuperAdmin::$roles,
                self::Lender => Lender::$roles,
            ]
        };
    }
}
