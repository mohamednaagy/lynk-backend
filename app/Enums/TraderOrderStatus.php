<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static InProgress()
 * @method static static Completed()
 * @method static static Expired()
 * @method static static Cancelled()
 */
final class TraderOrderStatus extends Enum
{
    const InProgress = 1;

    const Completed = 2;

    const Expired = 3;

    const Cancelled = 4;
}
