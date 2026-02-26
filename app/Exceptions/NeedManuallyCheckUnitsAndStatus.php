<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class NeedManuallyCheckUnitsAndStatus extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::ERROR_CHEKING_UNITS;
    }

    protected function errorMessage(): string
    {
        return 'Need to manually check the units count and update the inventory status';
    }
}
