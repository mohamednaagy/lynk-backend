<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderStatusDoesNotFollowSequenceException extends Exception
{
    public function render(Request $request)
    {
        $message = __('error.order_status_doesnt_follow_sequence');
        $code = Response::HTTP_BAD_REQUEST;
        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE
            );
        }
        abort($code, $message);
    }
}
