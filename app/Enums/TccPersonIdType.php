<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TccPersonIdType extends Enum
{
    const Citizen = 1;

    const Resident = 2;

    const BorderNumber = 3;

    const VisaNumber = 4;

    const PassportNumber = 5;
}
