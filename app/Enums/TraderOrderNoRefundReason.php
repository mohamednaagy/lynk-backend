<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class TraderOrderNoRefundReason extends Enum implements LocalizedEnum
{
    const AFTER_24_HOUR = 1;

    const AFTER_72_HOUR = 2;
}
