<?php

namespace Modules\Grantify\Support\Roles;

use App\Enums\Action;
use App\Enums\Subject;

class LenderSupervisor
{
    public static array $basePermissions = [
        Subject::Dashboard => [
            Action::Show,
        ],
        Subject::FinancingOrders => [
            Action::Manage,
            Action::Approve,
        ],
        Subject::LenderTransactions => [
            Action::Index,
        ],
        Subject::LenderEdaatInvoices => [
            Action::Manage,
        ],
        Subject::Enquiries => [
            Action::Index,
            Action::Create,
            Action::Show,
        ],
        Subject::LenderFinancingOrderCost => [
            Action::Calculate,
        ],
        Subject::LenderWallet => [
            Action::Manage,
        ],
    ];
}
