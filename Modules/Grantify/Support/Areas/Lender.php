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
        Subject::Stats => [
            Action::Show,
        ],
        Subject::Invitation => [
            Action::Resend,
        ],
        Subject::Balance => [
            Action::Show,
        ],
        Subject::Transactions => [
            Action::Show,
        ],
        Subject::OrderCost => [
            Action::Calculate,
        ],
    ];
}
