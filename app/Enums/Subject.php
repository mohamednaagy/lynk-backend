<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Admins()
 * @method static static Customer()
 */
final class Subject extends Enum
{
    const All = 'all';

    const Admins = 'admins';

    const Customers = 'customers';

    const Roles = 'roles';

    const Permissions = 'permissions';

    const FinancingOrders = 'financingOrders';

    const LenderWallet = 'lenderWallet';

    const LenderUsers = 'lenderUsers';

    const LenderSettings = 'lenderSettings';

    const Dashboard = 'dashboard';

    const LenderEdaatInvoices = 'lenderEdaatInvoices';
}
