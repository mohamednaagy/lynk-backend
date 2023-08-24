<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class OrderFeeType extends Enum
{
    const Fixed = 'fixed';

    const Proration = 'proration';
}
