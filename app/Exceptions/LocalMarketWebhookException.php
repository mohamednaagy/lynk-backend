<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Illuminate\Http\Response;

class LocalMarketWebhookException extends BaseApiException
{
    protected int $httpStatus = Response::HTTP_UNPROCESSABLE_ENTITY;

    protected function errorCode(): int
    {
        return ErrorCode::LOCAL_MARKET_WEBHOOK_INVALID_CASE;
    }

    protected function errorMessage(): string
    {
        return __('error.invalid_case_local_market');
    }
}
