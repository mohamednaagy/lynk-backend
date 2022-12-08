<?php

namespace Modules\Grantify\Support\Roles;

use App\Enums\Action;
use App\Enums\Subject;

class LenderApiUser
{
    public static array $basePermissions = [
        Subject::FinancingOrders => [
            Action::Create,
            Action::Index,
            Action::Show,
            Action::Proceed,
        ],
        Subject::LenderFinancingOrderCost => [
            Action::Calculate,
        ],
    ];
}
