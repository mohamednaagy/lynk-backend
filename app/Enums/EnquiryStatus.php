<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EnquiryStatus extends Enum
{
    const UnderReview = 1;

    const Resolved = 2;

    const Closed = 3;
}
