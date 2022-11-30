<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TccResponseCode extends Enum
{
    const MobileNumberMatched = 1200;

    const MobileNumberUnmatched = 1201;

    const InvalidMobileNumber = 1202;

    const InvalidRequestFormat = 709;

    const InvalidApiKey = 711;

    const ServiceNotAvailable = 715;

    const InvalidNationality = 718;

    const InvalidPersonIdType = 719;

    const InvalidOperatorTcn = 732;

    const PersonNotFound = 747;

    const InvalidPersonId = 702;
}
