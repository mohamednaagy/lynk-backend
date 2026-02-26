<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class OrderHasCompletedTraderOrderException extends BaseApiException
{
    public function __construct(private int $orderId)
    {
        parent::__construct();
    }

    protected function errorCode(): int
    {
        return ErrorCode::ORDER_HAS_COMPLETED_TRADER_ORDER;
    }

    protected function errorMessage(): string
    {
        return __('error.order_has_completed_trader_order', [
            'order_id' => $this->orderId,
        ]);
    }
}
