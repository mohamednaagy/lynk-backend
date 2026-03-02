<?php

namespace App\Exceptions;

use App\Enums\ErrorCode;

class OrderStatusDoesNotFollowSequenceException extends BaseApiException
{
    public function __construct(
        protected array $context = [],
        ?string $message = null,
    ) {
        parent::__construct($message ?? __('error.order_status_doesnt_follow_sequence', [
            'financingOrderId' => $this->context['financingOrderId'],
            'traderOrderId' => $this->context['traderOrderId'],
        ]));
    }

    protected function errorCode(): int
    {
        return ErrorCode::ORDER_STATUS_DOESNT_FOLLOW_SEQUENCE;
    }
}
