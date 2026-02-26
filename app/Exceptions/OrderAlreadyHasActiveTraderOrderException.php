<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class OrderAlreadyHasActiveTraderOrderException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::ORDER_ALREADY_HAS_ACTIVE_TRADER_ORDER;
    }

    protected function errorMessage(): string
    {
        return __('error.order_already_has_active_trader_order');
    }
}
