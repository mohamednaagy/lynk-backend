<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class CompanyNewOrderNotificationForAdminStatus extends Enum
{
    const Off = 0;

    const On = 1;
}
