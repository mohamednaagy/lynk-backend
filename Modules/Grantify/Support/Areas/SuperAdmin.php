<?php

namespace Modules\Grantify\Support\Areas;

use App\Enums\Action;
use App\Enums\Role;
use App\Enums\Subject;

class SuperAdmin
{
    public static array $roles = [
        Role::Admin,
        Role::Management,
    ];

    public static array $basePermissions = [
        Subject::Lenders => [
            Action::Index,
            Action::Create,
            Action::Edit,
            Action::Show,
            Action::Delete,
        ],
        Subject::Dashboard => [
            Action::Show,
        ],
        Subject::LenderWallet => [
            Action::Show,
        ],
        Subject::LenderUsers => [
            Action::Index,
            Action::Create,
            Action::Edit,
            Action::Show,
            Action::Delete,
        ],
        Subject::FinancingOrders => [
            Action::Index,
            Action::Create,
            Action::Edit,
            Action::Show,
            Action::Delete,
        ],
        Subject::LenderEdaatInvoices => [
            Action::Index,
            Action::Show,
            Action::SyncStatusWithEdaat,
        ],
        Subject::Admins => [
            Action::Index,
            Action::Create,
            Action::Edit,
            Action::Show,
            Action::Delete,
        ],
        Subject::Enquiries => [
            Action::Index,
            Action::Show,
        ],
        Subject::EnquiryReplies => [
            Action::Index,
            Action::Create,
        ],
    ];
}
