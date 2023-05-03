<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class EnquiryStatus extends Enum implements LocalizedEnum
{
    const UnderReview = 1;

    const Resolved = 2;

    const Closed = 3;
}
