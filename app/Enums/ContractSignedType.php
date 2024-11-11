<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class ContractSignedType extends Enum implements LocalizedEnum
{
    const Sell = 1;

    const Delivery = 2;
}
