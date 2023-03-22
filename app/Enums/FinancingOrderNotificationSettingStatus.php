<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class FinancingOrderNotificationSettingStatus extends Enum
{
    const BasedOnCompanySettings = 1;

    const On = 2;

    const Of = 3;
}
