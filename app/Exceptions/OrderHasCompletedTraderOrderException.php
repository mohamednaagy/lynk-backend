<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class OrderHasCompletedTraderOrderException extends Exception
{
    public function __construct(private int $orderId, string $message = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render(Request $request)
    {
        $message = __('error.order_has_completed_trader_order', [
            'order_id' => $this->orderId,
        ]);
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->errorResponse(
                $message,
                $code,
                ErrorCode::ORDER_HAS_COMPLETED_TRADER_ORDER
            );
        }

        abort($code, $message);
    }
}
