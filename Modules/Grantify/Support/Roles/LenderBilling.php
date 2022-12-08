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
        Subject::LenderTransactions => [
            Action::Manage,
        ],
    ];
}
