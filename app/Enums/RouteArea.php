<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class RouteArea extends Enum
{
    const Admin = 'admin';

    const Lender = 'lender';

    const Trader = 'trader';

    const Supplier = 'supplier';
}
