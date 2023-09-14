<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NoMatchOrderCostAndValueException extends Exception
{
    public function render(Request $request)
    {
        $message = __('error.no_match_for_order_cost_and_value');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::COMPANY_NO_MATCH_ORDER_COST_AND_VALUE
            );
        }

        abort($code, $message);
    }
}
