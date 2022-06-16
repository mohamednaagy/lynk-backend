<?php

namespace Modules\Permission\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Dashboard()
 * @method static static Customer()
 */
final class Subject extends Enum
{
    const Admins = 'Admins';
    const Customers = 'Customers';
}
