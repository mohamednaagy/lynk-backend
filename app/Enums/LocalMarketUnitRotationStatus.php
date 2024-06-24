<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * @method static static True()
 * @method static static False()
 */
final class LocalMarketUnitRotationStatus extends Enum implements LocalizedEnum
{
    const True = '1';

    const False = '0';
}
