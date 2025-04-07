<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class CompanyLenderClientType extends Enum implements LocalizedEnum
{
    /**
     * @method static static Business()
     * @method static static Individual()
     */
    const Business = 1;

    const Individual = 2;
}
