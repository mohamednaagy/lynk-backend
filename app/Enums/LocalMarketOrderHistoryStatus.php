<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class LocalMarketOrderHistoryStatus extends Enum implements LocalizedEnum
{
    const pendingBuying = 1;

    const SearchingForEligableUnits = 2;

    const changeownershiptocompany = 3;

    const completeBuy = 4;

    const pendingSelling = 5;

    const completeSelling = 6;

    const pendingCancelation = 7;

    const completeCancelation = 8;

    const cancelled = 9;
}
