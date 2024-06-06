<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class OrderStatus extends Enum implements LocalizedEnum
{
    const InProgress = 1;

    const Completed = 2;

    const Blocked = 3;

    const Cancelled = 4;

    const Refunded = 5;
}
