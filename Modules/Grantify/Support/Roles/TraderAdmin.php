<?php

namespace Modules\Grantify\Support\Roles;

use App\Enums\Action;
use App\Enums\Subject;

class TraderAdmin
{
    public static array $basePermissions = [
        Subject::All => [
            Action::Manage,
        ],
        Subject::FinancingOrders => [
            Action::Index,
            Action::Show,
            Action::Manage,
        ],
    ];
}
