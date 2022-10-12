<?php
namespace App\Enums;

use BenSampo\Enum\Enum;
use BenSampo\Enum\Contracts\LocalizedEnum;

final class FinancingOrderStatus extends Enum implements LocalizedEnum
{
    const Pending = 0;
    const Holding = 1;
    const Canceled = 2;
    const Completed = 3;
}
