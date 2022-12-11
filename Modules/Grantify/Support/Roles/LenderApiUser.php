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
            Action::Cancel,
        ],
        Subject::LenderFinancingOrderCost => [
            Action::Calculate,
        ],
        Subject::LenderWebhookSecret => [
            Action::Refresh,
        ],
        Subject::LenderWebhooks => [
            Action::Create,
        ],
        Subject::LenderWallet => [
            Action::Manage,
        ],
    ];
}
