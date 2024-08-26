<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class LocalMarketOrderStatus extends Enum implements LocalizedEnum
{
    const initiate = 0;

    const PendingEligibleCommodities = 1;

    const CommoditiesPurchased = 2;

    const EligibleCommoditiesAvailable = 3;

    const NoEligibleCommoditiesAvailable = 4;

    const FailedPurchase = 5;

    const pendingCancellation = 6;

    const Cancelled = 7;

    const Completed = 8;
}
