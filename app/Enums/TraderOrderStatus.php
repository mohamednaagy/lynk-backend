<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class TraderOrderStatus extends Enum implements LocalizedEnum
{
    const InProgress = 1;

    const Completed = 2;

    const Expired = 3;

    const Canceled = 4;
}
