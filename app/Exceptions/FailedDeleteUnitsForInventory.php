<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class FailedDeleteUnitsForInventory extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::ERROR_DELETING_UNITS;
    }

    protected function errorMessage(): string
    {
        return 'Failed to delete inventory';
    }
}
