<?php

namespace App\Exceptions\LocalMarket;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;
use Throwable;

class FailedToHoldRequiredUnitsException extends BaseApiException
{
    public function __construct(
        private int $requestedUnits,
        private int $heldUnits,
        private int $inventoryId,
        private ?int $orderId = null,
        string $message = '',
        ?Throwable $previous = null
    ) {
        parent::__construct($message ?: sprintf(
            'Failed to hold required units. Requested: %d, Held: %d for inventory ID: %d%s',
            $this->requestedUnits,
            $this->heldUnits,
            $this->inventoryId,
            $this->orderId ? ", order ID: {$this->orderId}" : ''
        ), ErrorCode::ERROR_HOLDING_UNITS, $previous);
    }

    protected function errorCode(): int
    {
        return ErrorCode::ERROR_HOLDING_UNITS;
    }
}
