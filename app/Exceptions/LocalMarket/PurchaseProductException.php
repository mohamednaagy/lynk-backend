<?php

namespace App\Exceptions\LocalMarket;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;

class PurchaseProductException extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        $message = trans('error.no_enough_balance');
        $code = ErrorCode::LOCAL_MARKET_PURCHASE_PRODUCT;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code
            );
        }

        abort($code, $message);
    }
}
