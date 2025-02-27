<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement\Enums;

use BenSampo\Enum\Enum;

final class UnitSettlementStatus extends Enum
{
    public const SoldToAnotherCustomer = 1;

    public const DeletedBySupplier = 2;
}
