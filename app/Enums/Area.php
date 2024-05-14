<?php

namespace App\Enums;

use BenSampo\Enum\Enum;
use Modules\Grantify\Support\Areas\CommoditySupplier;
use Modules\Grantify\Support\Areas\Lender;
use Modules\Grantify\Support\Areas\SuperAdmin;

/**
 * @method static static SuperAdmin()
 */
final class Area extends Enum
{
    const SuperAdmin = 'SuperAdmin';

    const Lender = 'Lender';

    const CommoditySupplier = 'CommoditySupplier';

    public static function roles(?string $area = null): array
    {
        return match ($area) {
            self::SuperAdmin => SuperAdmin::$roles,
            self::Lender => Lender::$roles,
            self::CommoditySupplier => CommoditySupplier::$roles,
            default => [
                self::SuperAdmin => SuperAdmin::$roles,
                self::Lender => Lender::$roles,
                self::CommoditySupplier => CommoditySupplier::$roles,
            ]
        };
    }

    public static function getAreaByRole(string $role)
    {
        $areas = self::getValues();
        foreach ($areas as $area) {
            if (in_array($role, self::roles($area))) {
                return $area;
            }
        }
    }
}
