<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class TraderOrderCancelReason extends Enum implements LocalizedEnum
{
    const Manual = 1;

    const MurabhaTimeout = 2;

    const FailureToPurchase = 3;

    const FinancingOrderIsCancelled = 4;

    const TraderOrderIsCancelled = 5;
    
    const NoEligibleCommoditiesAvailable = 6;

    const ExpiredContractSignTime = 7;
}
