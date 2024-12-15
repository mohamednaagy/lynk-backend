<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TraderOrderTimeLimitStatus extends Enum
{
    /**
     * Default status is "pending" when a time limit is created.
     */
    const Pending = 1;

    /**
     * Status changes to "expired" if the job is fired and the order is canceled.
     */
    const Expired = 2;

    /**
     * Status is set to "canceled" if the time limit is no longer needed and the required action occurs.
     */
    const Canceled = 3;

    /**
     * Status is set to "failed" if there is an error while executing the time limit.
     */
    const Failed = 4;
}
