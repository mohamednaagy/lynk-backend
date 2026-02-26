<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Illuminate\Support\Facades\Log;

class ErrorCreatingUnitsForThisInventory extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::ERROR_CREATING_UNITS;
    }

    protected function errorMessage(): string
    {
        return 'Error creating units for this inventory';
    }

    public function report(): void
    {
        Log::channel('local_market')->error($this->errorMessage());
    }
}
