<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

/**
 * @method static static Pending()
 * @method static static Paid()
 * @method static static Expired()
 */
final class EdaatInvoiceStatus extends Enum implements LocalizedEnum
{
    const Pending = 1;

    const Paid = 2;

    const Expired = 3;
}
