<?php

declare(strict_types=1);

namespace App\Enums;

enum SystemNotificationType: string
{
    case TRADE_REQUEST_CANCELLED = 'trade_request_cancelled';

    case ORDER_CANCELLED = 'order_cancelled';

    case ORDER_REQUIRES_APPROVAL = 'order_requires_approval';

    case DELIVERY_CONFIRMATION_RECEIVED = 'delivery_confirmation_received';

    case ORDER_APPROVED = 'order_approved';

    case LENDER_REGISTERED = 'lender_registered';

    case ORDERS_REPORT_EXPORT_READY = 'orders_report_export_ready';

    case TRADE_REQUEST_EXPIRED = 'trade_request_expired';

    case IN_PROGRESS_ORDERS = 'in_progress_orders';
}
