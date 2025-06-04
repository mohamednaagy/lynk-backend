<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * @method static static Active()
 * @method static static Inactive()
 */
final class CommodityTypeProvider extends Enum implements LocalizedEnum
{
    public const Lynk = 'lynk';

    public const Bursam = 'bursam';
}
