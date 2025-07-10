<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TradeRequestCreationNotAllowedException extends Exception
{
    public function render(Request $request)
    {
        $message = __('error.trade_request_creation_not_allowed');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::TRADE_REQUEST_CREATION_NOT_ALLOWED
            );
        }

        abort($code, $message);
    }
}
