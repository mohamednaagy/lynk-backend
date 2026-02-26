<?php

namespace App\Exceptions\LocalMarket;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;

class PurchaseProductException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::LOCAL_MARKET_PURCHASE_PRODUCT;
    }

    protected function errorMessage(): string
    {
        return trans('error.no_enough_balance');
    }
}
