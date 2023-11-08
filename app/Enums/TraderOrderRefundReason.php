<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class TraderOrderRefundReason extends Enum implements LocalizedEnum
{
    const WITHIN_24_HOUR = 1;

    const WITHIN_72_HOUR = 2;
}
