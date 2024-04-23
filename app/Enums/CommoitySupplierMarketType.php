<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * @method static static Local()
 * @method static static International()
 */
final class CommoitySupplierMarketType extends Enum implements LocalizedEnum
{
    const Local = 1;

    const International = 2;
}
