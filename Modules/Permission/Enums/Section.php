<?php

namespace Modules\Permission\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Dashboard()
 * @method static static Customer()
 */
final class Section extends Enum
{
    const Dashboard = 'Dashboard';
    const Customer = 'Customer';
}
