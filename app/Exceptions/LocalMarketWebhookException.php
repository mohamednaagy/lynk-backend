<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LocalMarketWebhookException extends Exception
{
    public function render(Request $request)
    {
        $message = __('error.invalid_case_local_market');
        $code = Response::HTTP_UNPROCESSABLE_ENTITY;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::LOCAL_MARKET_WEBHOOK_INVALID_CASE
            );
        }

        abort($code, $message);
    }
}
