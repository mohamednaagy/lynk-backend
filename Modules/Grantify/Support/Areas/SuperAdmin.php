<?php

namespace Modules\Grantify\Support\Areas;

use App\Enums\Action;
use App\Enums\Role;
use App\Enums\Subject;

class SuperAdmin
{
    public static array $roles = [
        Role::Admin,
        Role::Manager,
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
            Action::Charge,
        ],
        Subject::LenderUsers => [
            Action::Index,
            Action::Create,
            Action::Edit,
            Action::Show,
            Action::Delete,
            Action::Manage,
        ],
        Subject::FinancingOrders => [
            Action::Index,
            Action::Create,
            Action::Edit,
            Action::Show,
            Action::Delete,
            Action::Cancel,
            Action::Approve,
            Action::Reject,
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
            Action::Manage,
        ],
        Subject::Enquiries => [
            Action::Index,
            Action::Show,
        ],
        Subject::EnquiryReplies => [
            Action::Show,
            Action::Create,
        ],

        Subject::WakalaTemplates => [
            Action::Index,
            Action::Edit,
            Action::Manage,
        ],
        Subject::LenderAreaSettings => [
            Action::Index,
            Action::Edit,
            Action::Manage,
        ],
        Subject::ProjectSettings => [
            Action::Index,
            Action::Edit,
            Action::Manage,
        ],
        Subject::LenderTransactions => [
            Action::Index,
        ],
        Subject::Traders => [
            Action::Index,
            Action::Create,
            Action::Edit,
            Action::Show,
            Action::Manage,
        ],
        Subject::TraderUserInvitation => [
            Action::Send,
        ],
        Subject::TraderUsers => [
            Action::Index,
            Action::Create,
            Action::Edit,
            Action::Show,
            Action::Delete,
            Action::Manage,
        ],
        Subject::CommodityMarket => [
            Action::Index,
        ],

        Subject::CommodityMarketSuppliers => [
            Action::Index,
            Action::Create,
            Action::Show,
            Action::Edit,
        ],

        Subject::CommodityMarketCommodityTypes => [
            Action::Index,
            Action::Create,
            Action::Show,
            Action::Edit,
        ],

        Subject::CommodityMarketCommodityItems => [
            Action::Index,
        ],

        Subject::CommoditySupplierUsers => [
            Action::Manage,
            Action::Index,
            Action::Show,
            Action::Create,
            Action::Edit,
        ],
    ];
}
