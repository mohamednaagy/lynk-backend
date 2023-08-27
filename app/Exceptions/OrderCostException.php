<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderCostException extends Exception
{
    public function render(Request $request)
    {
        $message = __('error.company_order_cost_invalid');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::COMPANY_ORDER_COST_INVALID
            );
        }

        abort($code, $message);
    }
}
