<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class TraderOrderTimeLimitType extends Enum implements LocalizedEnum
{
    const ContractSignTimeLimit = 1;

    const DeliveryConfirmationTimeLimit = 2;
}
