<?php

namespace Modules\Permission\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Admin()
 * @method static static Customer()
 */
final class Role extends Enum
{
    const Admin = 'Admin';
    const Customer = 'Customer';
}
