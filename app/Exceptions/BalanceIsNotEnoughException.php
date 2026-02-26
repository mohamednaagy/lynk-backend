<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class BalanceIsNotEnoughException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::BALANCE_NOT_ENOUGH;
    }

    protected function errorMessage(): string
    {
        return trans('error.no_enough_balance');
    }
}
