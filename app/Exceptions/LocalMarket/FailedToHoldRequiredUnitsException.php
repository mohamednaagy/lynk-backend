<?php

namespace App\Exceptions\LocalMarket;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class FailedToHoldRequiredUnitsException extends Exception
{
    public function __construct(
        private int $requestedUnits,
        private int $heldUnits,
        private int $inventoryId,
        private ?int $orderId = null,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $msg = $message ?: sprintf(
            'Failed to hold required units. Requested: %d, Held: %d for inventory ID: %d%s',
            $this->requestedUnits,
            $this->heldUnits,
            $this->inventoryId,
            $this->orderId ? ", order ID: {$this->orderId}" : ''
        );

        parent::__construct($msg, $code, $previous);
    }

    public function render(Request $request)
    {
        $message = $this->getMessage();
        $code = Response::HTTP_BAD_REQUEST;

        if ($request->expectsJson()) {
            return response()->json([
                'error' => $message,
                'error_code' => ErrorCode::ERROR_HOLDING_UNITS,
            ], $code);
        }

        abort($code, $message);
    }
}
