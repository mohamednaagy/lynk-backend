<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement\Enums;

use BenSampo\Enum\Enum;

final class CommoditySettlementStatus extends Enum
{
    public const PendingSettlement = 1;

    public const CommoditySettled = 2;

    public const SettlementConfirmed = 3;

    public const SettlementFailed = 4;

    public const SettlementCanceled = 5;
}
