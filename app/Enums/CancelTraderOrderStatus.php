<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static PendingCancellation()
 * @method static static Cancelled()
 */
final class CancelTraderOrderStatus extends Enum
{
    const PendingCancellation = 1;

    const Cancelled = 2;
}
