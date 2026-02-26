<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class OrderRequiresClientVerification extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::ORDER_REQUIRE_CLIENT_VERIFICATION;
    }

    protected function errorMessage(): string
    {
        return __('error.order_require_client_verification');
    }
}
