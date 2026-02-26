<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class TradeRequestCreationNotAllowedException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::TRADE_REQUEST_CREATION_NOT_ALLOWED;
    }

    protected function errorMessage(): string
    {
        return __('error.trade_request_creation_not_allowed');
    }
}
