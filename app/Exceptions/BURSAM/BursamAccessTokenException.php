<?php

namespace App\Exceptions\BURSAM;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;

class BursamAccessTokenException extends Exception
{
    public function render(Request $request)
    {
        $code = ErrorCode::CAN_NOT_DEAL_WITH_BURSAM_SYSTEM;
        $message = 'Error while getting access token from Bursam';

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code
            );
        }

        abort($code, $message);
    }
}
