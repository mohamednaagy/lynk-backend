<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static Pending()
 * @method static static Paid()
 * @method static static Expired()
 */
final class EdaatInvoiceStatus extends Enum
{
    const Pending = 1;

    const Paid = 2;

    const Expired = 3;
}
