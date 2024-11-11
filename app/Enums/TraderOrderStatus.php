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

    const PurchasingFailure = 6;

    const FailureToProgress = 7;

    const FailureToCancel = 8;

    const Hold = 9;

    public static $inProgressOrComplete = [
        self::Completed,
        self::InProgress,
    ];
}
