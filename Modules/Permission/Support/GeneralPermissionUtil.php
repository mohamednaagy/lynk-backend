<?php

namespace Modules\Permission\Support;

use App\Enums\Action;
use App\Enums\Area;
use App\Enums\Subject;

class GeneralPermissionUtil
{
    public static array $areaGeneralPermissionMap = [
        Area::SuperAdmin => [
            'General' => [
                Subject::Admins => [
                    Action::getRoles,
                    Action::getPermissions
                ]
            ]
        ],
        Area::Customer => [
            'General' => [

            ]
        ]
    ];

    /**
     * @param string $areaName
     * @return array
     */
    public static function getGeneralPermissionsForArea(string $areaName): array
    {
        return self::$areaGeneralPermissionMap[$areaName];
    }
}
