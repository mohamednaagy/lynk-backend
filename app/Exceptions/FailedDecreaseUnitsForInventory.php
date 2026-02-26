<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Illuminate\Support\Facades\Log;

class FailedDecreaseUnitsForInventory extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::ERROR_CHEKING_UNITS;
    }

    protected function errorMessage(): string
    {
        return 'Failed to decrease quantity for inventory';
    }

    public function report(): void
    {
        Log::channel('local_market')->error($this->errorMessage());
    }
}
