<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderIsAlreadyCompletedException extends Exception
{
    public function render(Request $request)
    {
        $message = __('error.order_is_already_completed');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::ORDER_IS_ALREADY_COMPLETED
            );
        }

        abort($code, $message);
    }
}
