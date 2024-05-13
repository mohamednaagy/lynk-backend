<?php

namespace Modules\Grantify\Support\Areas;

use App\Enums\Action;
use App\Enums\Role;
use App\Enums\Subject;

class CommoditySupplier
{
    public static array $roles = [
        Role::SupplierAdmin,
    ];

    public static array $basePermissions = [
        Subject::CommoditySupplierUsers => [
            Action::Manage,
            Action::Index,
            Action::Show,
            Action::Create,
            Action::Edit,
        ],
    ];
}
