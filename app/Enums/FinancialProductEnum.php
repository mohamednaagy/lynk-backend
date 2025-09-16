<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancialProductEnum extends Enum implements LocalizedEnum
{
    const NormalLending = 1;

    const SpecialPurposeVehicle = 2;

    const TimeDeposit = 3;
}
