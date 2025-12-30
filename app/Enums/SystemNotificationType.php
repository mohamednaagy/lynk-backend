<?php

namespace App\Enums;

enum SystemNotificationType: string
{
    case TRADE_REQUEST_CANCELLED = 'trade_request_cancelled';

    case ORDER_CANCELLED = 'order_cancelled';

    case ORDER_REQUIRES_APPROVAL = 'order_requires_approval';

    case DELIVERY_CONFIRMATION_RECEIVED = 'delivery_confirmation_received';

    case ORDER_CREATED = 'order_created';

    case ORDER_APPROVED = 'order_approved';

    case LENDER_REGISTERED = 'lender_registered';

    case NEW_SIGN_IN = 'new_sign_in';

    public static function getAdminNotificationTypes(): array
    {
        return [
            self::TRADE_REQUEST_CANCELLED,
            self::ORDER_CANCELLED,
            self::ORDER_REQUIRES_APPROVAL,
            self::DELIVERY_CONFIRMATION_RECEIVED,
            self::ORDER_APPROVED,
            self::LENDER_REGISTERED,
            self::NEW_SIGN_IN,
        ];
    }

    public static function getLenderNotificationTypes(): array
    {
        return [
            self::TRADE_REQUEST_CANCELLED,
            self::ORDER_CANCELLED,
            self::ORDER_REQUIRES_APPROVAL,
            self::ORDER_CREATED,
            self::ORDER_APPROVED,
            self::LENDER_REGISTERED,
            self::NEW_SIGN_IN,
        ];
    }
}
