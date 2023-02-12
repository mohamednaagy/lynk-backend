<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class CompanyType extends Enum implements LocalizedEnum
{
    const Lender = 1;

    const Trader = 2;
}
