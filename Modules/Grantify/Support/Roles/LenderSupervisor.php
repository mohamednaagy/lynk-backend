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
        ],
        Subject::LenderTransactions => [
            Action::Manage,
        ],
        Subject::LenderEdaatInvoices => [
            Action::Manage,
        ],
    ];
}
