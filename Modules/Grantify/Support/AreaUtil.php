<?php

namespace Modules\Grantify\Support;

use App\Enums\Area;
use Modules\Grantify\Support\Areas\Lender;
use Modules\Grantify\Support\Areas\SuperAdmin;

class AreaUtil
{
    public static array $areaMap = [
        Area::SuperAdmin => SuperAdmin::class,
        Area::Lender => Lender::class,
    ];

    /**
     * @param  string  $areaName
     * @return array|string
     */
    public static function getAreaPermissions(string $areaName): array|string
    {
        if (isset(self::$areaMap[$areaName])) {
            $area = self::$areaMap[$areaName];

            return $area::$basePermissions;
        }

        return [];
    }
}
