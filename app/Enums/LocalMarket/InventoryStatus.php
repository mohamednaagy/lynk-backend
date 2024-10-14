<?php

namespace App\Enums\LocalMarket;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * @method static static Active()
 * @method static static Inactive()
 */
final class InventoryStatus extends Enum implements LocalizedEnum
{
    const Pending = '0';

    const Active = '1';

    const Inactive = '2';

    const Problem = '3';
}
