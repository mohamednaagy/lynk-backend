<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * @method static static Pending()
 * @method static static InProgress()
 * @method static static Completed()
 * @method static static Failed()
 */
final class TraderOrderSettlementStatus extends Enum implements LocalizedEnum
{
    public const Pending = 0;

    public const InProgress = 1;

    public const Completed = 2;

    public const Failed = 3;
}
