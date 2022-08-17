<?php

namespace Modules\Permission\Support;

use Modules\Permission\Enums\Action;
use Modules\Permission\Enums\Area;
use Modules\Permission\Enums\Subject;

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
