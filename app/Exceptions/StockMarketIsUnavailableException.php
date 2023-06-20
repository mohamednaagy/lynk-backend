<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StockMarketIsUnavailableException extends Exception
{
    public function render(Request $request)
    {
        $message = __('error.stock_market_is_unavailable');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::STOCK_MARKET_IS_UNAVAILABLE
            );
        }

        abort($code, $message);
    }
}
