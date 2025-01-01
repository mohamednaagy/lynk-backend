<?php

namespace App\Enums\LocalMarket;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * @method static static Active()
 * @method static static Inactive()
 */
final class SupplierStatus extends Enum implements LocalizedEnum
{
    public const Active = 1;

    public const Inactive = 2;
}
