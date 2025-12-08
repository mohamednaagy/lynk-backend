<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class SystemNotificationType extends Enum implements LocalizedEnum
{
    const TRADE_REQUEST_CANCELLED = 'trade_request_cancelled';

    const ORDER_CANCELLED = 'order_cancelled';

    const ORDER_REQUIRES_APPROVAL = 'order_requires_approval';

    const DELIVERY_CONFIRMATION_RECEIVED = 'delivery_confirmation_received';
}
