<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class Subject extends Enum
{
    const All = 'all';

    const Admins = 'admins';

    const Customers = 'customers';

    const Roles = 'roles';

    const Permissions = 'permissions';

    const FinancingOrders = 'financingOrders';

    const LenderWallet = 'lenderWallet';

    const LenderFinancingOrderCost = 'lenderFinancingOrderCost';

    const LenderUsers = 'lenderUsers';

    const LenderSettings = 'lenderSettings';

    const LenderTransactions = 'lenderTransactions';

    const Dashboard = 'dashboard';

    const LenderEdaatInvoices = 'lenderEdaatInvoices';

    const Lenders = 'lenders';

    const Enquiries = 'enquiries';

    const EnquiryReplies = 'enquiryReplies';
}
