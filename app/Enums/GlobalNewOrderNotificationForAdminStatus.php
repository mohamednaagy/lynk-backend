<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class GlobalNewOrderNotificationForAdminStatus extends Enum
{
    const Off = 0;

    const On = 1;

    const BasedOnCompanySettings = 2;
}
