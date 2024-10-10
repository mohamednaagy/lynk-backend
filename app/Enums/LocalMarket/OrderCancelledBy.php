<?php

namespace App\Enums\LocalMarket;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class OrderCancelledBy extends Enum implements LocalizedEnum
{
    const Customer = 1;
}
