<?php

namespace Modules\Grantify\Support\Roles;

use App\Enums\Action;
use App\Enums\Subject;

class LenderOrderCreator
{
    public static array $basePermissions = [
        Subject::LenderUsers => [
            Action::Show,
            Action::Edit,
        ],
        Subject::FinancingOrders => [
            Action::Create,
            Action::Index,
            Action::Show,
            Action::Edit,
            Action::Proceed,
        ],
        Subject::Enquiries => [
            Action::Index,
            Action::Create,
            Action::Show,
        ],
        Subject::CommodityMarketCommodityTypes => [
            Action::Index,
        ],
        Subject::FinancialProducts => [
            Action::Index,
        ],
    ];
}
