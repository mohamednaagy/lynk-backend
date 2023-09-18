<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static PendingCancellation()
 * @method static static Cancelled()
 */
final class CancelOrderStatus extends Enum
{
    const PendingCancellation = 0;

    const Cancelled = 1;
}
