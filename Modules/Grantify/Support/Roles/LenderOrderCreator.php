<?php

namespace Modules\Grantify\Support\Roles;

use App\Enums\Action;
use App\Enums\Subject;

class LenderOrderCreator
{
    public static array $basePermissions = [
        Subject::FinancingOrders => [
            Action::Create,
            Action::Index,
            Action::Show,
            Action::Edit,
        ],
    ];
}
