<?php

namespace App\Exceptions\BURSAM;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BursamAccessTokenException extends Exception
{
    public function render(Request $request)
    {
        $code = ErrorCode::CAN_NOT_DEAL_WITH_BURSAM_SYSTEM;
        $message = 'Error while getting access token from Bursam';

        Log::error('Error While Trying To Get Token From BURSAM for request details check BURSAM log files.');

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code
            );
        }

        abort($code, $message);
    }
}
