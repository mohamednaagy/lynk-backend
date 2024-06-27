<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderIsCancelledException extends Exception
{
    public function render(Request $request)
    {
        $message = __('error.order_is_already_cancelled');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::ORDER_IS_CANCELLED
            );
        }

        abort($code, $message);
    }
}
