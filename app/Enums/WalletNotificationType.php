<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class WalletNotificationType extends Enum implements LocalizedEnum
{
    const ORDER_COUNT = 1;

    const WALLET_BALANCE = 2;
}
