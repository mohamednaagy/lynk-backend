<?php

namespace App\Enums\LocalMarket;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * @method static static Active()
 * @method static static Inactive()
 */
final class InventoryUnitsStatus extends Enum implements LocalizedEnum
{
    const Free = 1;

    const Reserved = 2;

    const OngoingProcess = 3;
}
