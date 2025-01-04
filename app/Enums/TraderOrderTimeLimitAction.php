<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * This class represents the possible actions to be taken when a trader's order reaches its time limit.
 */
final class TraderOrderTimeLimitAction extends Enum
{
    /**
     * No action is needed when the order reaches its time limit.
     */
    const NoActionNeeded   =   1;

    /**
     * Automatically cancel the order when it reaches its time limit.
     */
    const AutoCancelOrder  =   2;
}
