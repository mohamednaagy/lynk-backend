<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class CompanyLenderClientType extends Enum implements LocalizedEnum
{
    /**
     * @method static static business()
     * @method static static individual()
     */
    const business = 1;

    const individual = 2;
}
