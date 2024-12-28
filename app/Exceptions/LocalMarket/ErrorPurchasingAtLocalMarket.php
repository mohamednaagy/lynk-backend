<?php

namespace App\Exceptions\LocalMarket;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ErrorPurchasingAtLocalMarket extends Exception
{
    /**
     * Render the exception into an HTTP response.
     *
     * @return \Illuminate\Http\Response
     */
    public function render(Request $request)
    {
        $message = 'Error When Purchasing From Local Marker Available Commodity != Eligible Commodity';
        $code = Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($request->expectsJson()) {
            dd('eeeee');

            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::LOCAL_MARKET_CANT_PURCHASING
            );
        }

        abort($code, $message);
    }
}
