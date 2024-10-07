<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class CustomerDeliveryStatus extends Enum implements LocalizedEnum
{
    const DeliveryPending = 0;

    const DeliveryConfirmed = 1;

    const DeliveryIgnoreAndSell = 2;
}
