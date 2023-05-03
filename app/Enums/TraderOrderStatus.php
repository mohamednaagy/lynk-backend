<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class TraderOrderStatus extends Enum implements LocalizedEnum
{
    const Initiated = 0;

    const InProgress = 1;

    const Completed = 2;

    const Expired = 3;

    const Cancelled = 4;

    const PendingCancellation = 5;

    public static $inProgressOrComplete = [
        self::Completed,
        self::InProgress,
    ];
}
