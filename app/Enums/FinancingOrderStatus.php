<?php
namespace App\Enums;

use BenSampo\Enum\Enum;
use BenSampo\Enum\Contracts\LocalizedEnum;

final class FinancingOrderStatus extends Enum implements LocalizedEnum
{
    const Pending = 1;
    const Holding = 2;
    const Canceled = 3;
    const Completed = 4;
}
