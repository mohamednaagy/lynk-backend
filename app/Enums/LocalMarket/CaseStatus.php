<?php

namespace App\Enums\LocalMarket;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class CaseStatus extends Enum implements LocalizedEnum
{
    const CommoditiesPurchased = 1;

    const NoEligibleCommoditiesAvailable = 2;

    const FailedPurchase = 3;

    const Cancelled = 4;

    const FailedSell = 5;

    const TransferOwnershipToCustomer = 6;

    const FailedToCancel = 7;
}
