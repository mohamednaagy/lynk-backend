<?php

namespace Modules\Grantify\Support\Areas;

use App\Enums\Action;
use App\Enums\Role;
use App\Enums\Subject;

class CommoditySupplier
{
    public static array $roles = [
        Role::SupplierAdmin,
        Role::SupplierApiAdmin,
    ];

    public static array $basePermissions = [
        Subject::CommoditySupplierUsers => [
            Action::Manage,
            Action::Index,
            Action::Show,
            Action::Create,
            Action::Edit,
        ],
        Subject::CommodityMarketCommodityTypes => [
            Action::Manage,
            Action::Index,
        ],

        Subject::ConstantApi => [
            Action::Manage,
            Action::Index,
        ],

        Subject::CommoditySupplierLocations => [
            Action::Manage,
            Action::Index,
            Action::Edit,
            Action::Create,
        ],

        Subject::CommoditySupplierItems => [
            Action::Manage,
            Action::Index,
            Action::Show,
            Action::Edit,
            Action::Create,
        ],

        Subject::CommoditySupplierInventories => [
            Action::Manage,
            Action::Index,
            Action::Show,
            Action::Edit,
            Action::Create,
        ],

    ];
}
