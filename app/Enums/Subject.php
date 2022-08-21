<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Admins()
 * @method static static Customer()
 */
final class Subject extends Enum
{
    const Admins = 'Admins';
    const Customers = 'Customers';
}
