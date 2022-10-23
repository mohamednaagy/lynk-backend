<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancingOrderStatus extends Enum implements LocalizedEnum
{
    const Pending = 1;

    const InProgress = 2;

    const Canceled = 3;

    const Completed = 4;
}
