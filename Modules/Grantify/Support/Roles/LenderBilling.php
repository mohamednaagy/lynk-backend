<?php

namespace Modules\Grantify\Support\Roles;

use App\Enums\Action;
use App\Enums\Subject;

class LenderBilling
{
    public static array $basePermissions = [
        Subject::LenderWallet => [
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
        Subject::LenderTransactions => [
            Action::Index,
        ],
        Subject::LenderEdaatInvoices => [
            Action::Manage,
        ],
    ];
}
