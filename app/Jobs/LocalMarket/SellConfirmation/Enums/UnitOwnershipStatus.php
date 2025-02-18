<?php

namespace App\Jobs\LocalMarket\SellConfirmation\Enums;

use BenSampo\Enum\Enum;

final class UnitOwnershipStatus extends Enum
{
    public const Owner = 1;

    public const SoldToAnotherCustomer = 2;

    public const DeletedBySupplier = 3;
}
