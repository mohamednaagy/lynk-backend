<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancingOrderStatus extends Enum implements LocalizedEnum
{
    const Pending = 1;

    const Canceled = 2;

    const Completed = 3;
}
