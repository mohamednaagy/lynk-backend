<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class NoMatchOrderCostAndValueException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::COMPANY_NO_MATCH_ORDER_COST_AND_VALUE;
    }

    protected function errorMessage(): string
    {
        return __('error.no_match_for_order_cost_and_value');
    }
}
