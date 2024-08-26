<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class LocalMarketOrderStatus extends Enum implements LocalizedEnum
{
    const PendingEligibleCommodities = 1;

    const CommoditiesPurchased = 2;

    const EligibleCommoditiesAvailable = 3;

    const NoEligibleCommoditiesAvailable = 4;

    const pendingCancellation = 5;

    const Completed = 6;

    const Cancelled = 7;
}
