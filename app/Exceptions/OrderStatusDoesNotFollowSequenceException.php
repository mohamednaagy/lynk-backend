<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderStatusDoesNotFollowSequenceException extends Exception
{
    protected $message;

    public function __construct(
        protected $context = [],
        ?string $message = null,
    ) {
        $this->message = $message ?? __('error.order_status_doesnt_follow_sequence', [
            'financingOrderId' => $this->context['financingOrderId'],
            'traderOrderId' => $this->context['traderOrderId'],
        ]);
        parent::__construct($this->message);

    }

    public function render(Request $request)
    {

        $code = Response::HTTP_BAD_REQUEST;
        if ($request->expectsJson()) {
            return response()->errorResponse(
                $this->message,
                $code,
                ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE
            );
        }
        abort($code, $this->message);
    }
}
