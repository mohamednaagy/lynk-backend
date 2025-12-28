<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class SystemNotificationType extends Enum implements LocalizedEnum
{
    public const TRADE_REQUEST_CANCELLED = 'trade_request_cancelled';

    public const ORDER_CANCELLED = 'order_cancelled';

    public const ORDER_REQUIRES_APPROVAL = 'order_requires_approval';

    public const DELIVERY_CONFIRMATION_RECEIVED = 'delivery_confirmation_received';

    public const ORDER_CREATED = 'order_created';

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
            self::ORDER_CREATED,
        ];
    }
}
