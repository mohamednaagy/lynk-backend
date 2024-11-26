<?php

namespace App\Enums\LocalMarket;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class UnitOwnershipAction extends Enum implements LocalizedEnum
{
    const PurchaseCommodity = 1; // (supplier or previous trade order)  to ( company )

    const BorrowerOwnershipTransfer = 2; // (company) => customer

    const SellCommodity = 3; // customer => trader order

    const Cancel = 4; // customer  => supplier or previous trade order

    const ConfirmedDelivery = 5; // customer => trade order
}
