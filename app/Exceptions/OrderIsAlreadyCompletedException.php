<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class OrderIsAlreadyCompletedException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::ORDER_IS_ALREADY_COMPLETED;
    }

    protected function errorMessage(): string
    {
        return __('error.order_is_already_completed');
    }
}
