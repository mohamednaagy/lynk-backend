<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class CommodityMarketIsUnavailableException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::COMMODITY_MARKET_IS_UNAVAILABLE;
    }

    protected function errorMessage(): string
    {
        return __('error.commodity_market_is_unavailable');
    }
}
