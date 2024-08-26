<?php

namespace App\Enums\LocalMarket;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * @method static static Active()
 * @method static static Inactive()
 */
final class OwnershipTypes extends Enum implements LocalizedEnum
{
    const Company = 1;

    const Customer = 2;

    const OriginalSupplier = 3;

    const LocalMarket = 4;
}
