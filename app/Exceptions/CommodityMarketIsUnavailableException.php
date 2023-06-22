<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommodityMarketIsUnavailableException extends Exception
{
    public function render(Request $request)
    {
        $message = __('error.commodity_market_is_unavailable');
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::COMMODITY_MARKET_IS_UNAVAILABLE
            );
        }

        abort($code, $message);
    }
}
