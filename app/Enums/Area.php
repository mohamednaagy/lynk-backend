<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use Modules\Grantify\Support\Areas\Customer;
use Modules\Grantify\Support\Areas\Lender;
use Modules\Grantify\Support\Areas\SuperAdmin;
use Modules\Grantify\Support\Areas\Trader;

/**
 * @method static static SuperAdmin()
 * @method static static Customer()
 */
final class Area extends Enum
{
    const SuperAdmin = 'SuperAdmin';

    const Customer = 'Customer';

    const Lender = 'Lender';

    const Trader = 'Trader';

    public static function roles(string $area = null): array
    {
        return match ($area) {
            self::Customer => Customer::$roles,
            self::SuperAdmin => SuperAdmin::$roles,
            self::Lender => Lender::$roles,
            self::Trader => Trader::$roles,
            default => [
                self::SuperAdmin => SuperAdmin::$roles,
                self::Lender => Lender::$roles,
                self::Trader => Trader::$roles,
            ]
        };
    }
}
