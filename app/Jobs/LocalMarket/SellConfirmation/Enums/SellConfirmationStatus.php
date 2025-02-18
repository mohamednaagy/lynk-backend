<?php

namespace App\Jobs\LocalMarket\SellConfirmation\Enums;

use BenSampo\Enum\Enum;

final class SellConfirmationStatus extends Enum
{
    public const Pending = 0;

    public const ReadyForCertificate = 1;

    public const Generated = 2;

    public const Error = 3;

    public const Skip = 4;
}
