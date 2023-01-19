<?php

namespace Modules\Grantify\Support\Areas;

use App\Enums\Action;
use App\Enums\Role;
use App\Enums\Subject;

class Trader
{
    public static array $roles = [
        Role::TraderAdmin,
    ];

    public static array $basePermissions = [
        Subject::FinancingOrders => [
            Action::Manage,
            Action::Index,
            Action::Show,
        ],
        Subject::TraderUsers => [
            Action::Manage,
            Action::Index,
            Action::Show,
            Action::Create,
            Action::Edit,
        ],
        Subject::TraderStatus => [
            Action::Manage,
            Action::Edit,
        ],
    ];
}
