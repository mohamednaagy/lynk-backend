<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class OrderIsCancelledException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::ORDER_IS_CANCELLED;
    }

    protected function errorMessage(): string
    {
        return __('error.order_is_already_cancelled');
    }
}
