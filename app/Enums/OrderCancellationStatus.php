<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static PendingCancellation()
 * @method static static Cancelled()
 */
class OrderCancellationStatus extends Enum
{
    const PendingCancellation = 1;

    const Cancelled = 2;
}
