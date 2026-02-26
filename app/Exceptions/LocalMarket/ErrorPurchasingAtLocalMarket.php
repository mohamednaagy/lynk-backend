<?php

namespace App\Exceptions\LocalMarket;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;
use Illuminate\Http\Response;

class ErrorPurchasingAtLocalMarket extends BaseApiException
{
    protected int $httpStatus = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected function errorCode(): int
    {
        return ErrorCode::LOCAL_MARKET_CANT_PURCHASING;
    }

    protected function errorMessage(): string
    {
        return 'Error When Purchasing From Local Marker Available Commodity != Eligible Commodity';
    }
}
