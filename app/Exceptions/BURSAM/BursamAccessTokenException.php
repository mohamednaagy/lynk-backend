<?php

namespace App\Exceptions\BURSAM;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;
use Illuminate\Support\Facades\Log;

class BursamAccessTokenException extends BaseApiException
{
    protected function errorCode(): int
    {
        return ErrorCode::CAN_NOT_DEAL_WITH_BURSAM_SYSTEM;
    }

    protected function errorMessage(): string
    {
        return 'Error while getting access token from Bursam';
    }

    public function report(): void
    {
        Log::channel(LOG_CHANNEL_BURSAM)->error('Error While Trying To Get Token From BURSAM for request details check BURSAM log files.');
    }
}
