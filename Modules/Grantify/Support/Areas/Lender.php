<?php

namespace Modules\Grantify\Support\Areas;

use App\Enums\Action;
use App\Enums\Subject;

class Lender
{
    public static array $basePermissions = [
        Subject::FinancingOrders => [
            Action::Manage,
            Action::Create,
            Action::Index,
            Action::Show,
            Action::Proceed,
            Action::Approve,
            Action::Reject,
            Action::Cancel,
            Action::GetStats,
        ],
        Subject::LenderWallet => [
            Action::Manage,
            Action::GetBalance,
            Action::CalculateOrderCost,
            Action::GetTransactions,
        ],
        Subject::Dashboard => [
            Action::Show,
        ],
        Subject::LenderEdaatInvoices => [
            Action::Manage,
            Action::Index,
            Action::Create,
        ],
        Subject::LenderUsers => [
            Action::ResendInvitation,
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
    ];
}
