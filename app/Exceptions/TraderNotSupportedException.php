<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class TraderNotSupportedException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::TRADER_NOT_SUPPORTED;
    }

    protected function errorMessage(): string
    {
        return trans('error.trader_not_supported');
    }
}
