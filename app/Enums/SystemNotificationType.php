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


    public static function getAdminNotificationTypes(): array
    {
        return [
            self::TRADE_REQUEST_CANCELLED,
            self::ORDER_CANCELLED,
            self::ORDER_REQUIRES_APPROVAL,
            self::DELIVERY_CONFIRMATION_RECEIVED,
        ];
    }


    public static function getLenderNotificationTypes(): array
    {
        return [
            self::TRADE_REQUEST_CANCELLED,
            self::ORDER_CANCELLED,
            self::ORDER_REQUIRES_APPROVAL,
        ];
    }
}
