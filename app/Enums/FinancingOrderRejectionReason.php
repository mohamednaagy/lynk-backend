<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancingOrderRejectionReason extends Enum implements LocalizedEnum
{
    const Rejected = 1;
}
