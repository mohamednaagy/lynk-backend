<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * @method static static Active()
 * @method static static Inactive()
 */
final class CommoitySupplierStatus extends Enum implements LocalizedEnum
{
    const Active = 1;

    const Inactive = 2;
}
