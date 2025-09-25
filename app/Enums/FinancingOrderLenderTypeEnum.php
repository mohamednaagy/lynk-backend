<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class FinancingOrderLenderTypeEnum extends Enum
{
    const NormalLending = 1;

    const SpecialPurposeVehicle = 2;

    const TimeDeposit = 3;
}
