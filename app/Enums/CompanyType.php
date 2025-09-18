<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class CompanyType extends Enum implements LocalizedEnum
{
    const Lender = 1;

    const Trader = 2;

    const Supplier = 3;

    const SpecialPurposeVehicle = 4;

    const TimeDeposit = 5;
}
