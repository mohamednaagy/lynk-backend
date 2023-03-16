<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderIsAlreadyHasActiveTraderOrderException extends Exception
{
    public function render(Request $request)
    {
        $message = __('error.order_is_already_has_active_trader_order');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::ORDER_IS_ALREADY_HAS_ACTIVE_TRADER_ORDER
            );
        }

        abort($code, $message);
    }
}
