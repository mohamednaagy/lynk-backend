<?php

namespace App\Enums\LocalMarket;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class OrderStatus extends Enum implements LocalizedEnum
{
    const Initiated = '0';

    const InProgress = '1';
}
