<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderHasNoActiveTradeRequestException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        $message = trans('error.order_has_no_active_trade_request');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::ORDER_HAS_NO_ACTIVE_TRADE_REQUEST
            );
        }

        abort($code, $message);
    }
}
