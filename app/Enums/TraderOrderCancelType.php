<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class TraderOrderCancelType extends Enum implements LocalizedEnum
{
    const User = 1;

    const System = 2;
}
