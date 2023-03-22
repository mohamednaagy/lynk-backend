<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class FinancingOrderNotificationStatus extends Enum
{
    const Of = 0;

    const On = 1;
}
