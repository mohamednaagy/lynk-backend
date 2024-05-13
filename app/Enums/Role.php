<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Admin()
 */
final class Role extends Enum
{
    const Admin = 'Admin';

    const Manager = 'Manager';

    const LenderAdmin = 'LenderAdmin';

    const LenderSupervisor = 'LenderSupervisor';

    const LenderBilling = 'LenderBilling';

    const LenderOrderCreator = 'LenderOrderCreator';

    const LenderApiUser = 'LenderApiUser';

    const TraderAdmin = 'TraderAdmin';

    const SupplierAdmin = 'SupplierAdmin';
}
