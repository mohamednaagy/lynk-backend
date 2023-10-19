<?php

namespace Modules\Grantify\Support\Areas;

use App\Enums\Action;
use App\Enums\Role;
use App\Enums\Subject;

class Lender
{
    public static array $roles = [
        Role::LenderAdmin,
        Role::LenderBilling,
        Role::LenderSupervisor,
        Role::LenderOrderCreator,
        Role::LenderApiUser,
    ];

    public static array $basePermissions = [
        Subject::FinancingOrders => [
            Action::Manage,
            Action::Create,
            Action::Index,
            Action::Show,
            Action::Approve,
            Action::Reject,
            Action::Cancel,
        ],
        Subject::LenderWallet => [
            Action::Manage,
        ],
        Subject::Dashboard => [
            Action::Show,
        ],
        Subject::LenderEdaatInvoices => [
            Action::Manage,
            Action::Index,
            Action::Create,
        ],
        Subject::Enquiries => [
            Action::Index,
            Action::Show,
            Action::Create,
        ],
        Subject::EnquiryReplies => [
            Action::Index,
            Action::Create,
        ],
        Subject::LenderTransactions => [
            Action::Index,
        ],
        Subject::LenderWebhooks => [
            Action::Index,
            Action::Create,
            Action::Delete,
        ],
        Subject::LenderWebhookSecret => [
            Action::Refresh,
        ],
        Subject::WalletNotifications => [
            Action::Manage,
            Action::Index,
            Action::Create,
        ],
    ];
}
