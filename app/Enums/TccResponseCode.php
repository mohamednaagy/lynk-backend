<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TccResponseCode extends Enum
{
    const MobileNumberMatched = 1200;

    const MobileNumberUnMatched = 1201;

    const InvalidMobileNumber = 1202;

    const PersonNotFound = 747;

    const InvalidPersonId = 702;
}
