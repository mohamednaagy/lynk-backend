<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class LocalMarketOrderStatus extends Enum implements LocalizedEnum
{
    const InProgress = 1;

    const Completed = 2;

    const Blocked = 3;

    const Cancelled = 4;

    const Refunded = 5;

    const PendingEligibleCommodities = 6;

    const EligibleCommoditiesFound = 7;

    const NoEligibleCommoditiesFound = 8;

    const BuyCommoditiesDone = 9;
}
