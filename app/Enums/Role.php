<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Admin()
 * @method static static Customer()
 */
final class Role extends Enum
{
    const Admin = 'Admin';

    const Customer = 'Customer';

    const LenderAdmin = 'LenderAdmin';

    const LenderSupervisor = 'LenderSupervisor';

    const LenderBilling = 'LenderBilling';

    const LenderOrderCreator = 'LenderOrderCreator';

    const LenderApiUser = 'LenderApiUser';
}
