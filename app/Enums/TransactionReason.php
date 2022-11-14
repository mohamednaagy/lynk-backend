<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class TransactionReason extends Enum
{
    const OrderCreationFee = 1;

    const DepositByEdaat = 2;

    const DepositByAdmin = 3;
}
