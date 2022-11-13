<?php

namespace App\Enums;

use BenSampo\Enum\Rules\Enum;

final class TransactionType extends Enum
{
    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAW = 'withdraw';
}
