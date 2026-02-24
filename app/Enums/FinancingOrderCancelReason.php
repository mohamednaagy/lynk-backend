<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancingOrderCancelReason extends Enum implements LocalizedEnum
{
    const Cancelled = 1;
}
