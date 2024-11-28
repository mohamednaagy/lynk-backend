<?php

namespace App\Services\TraderOrder;

use App\Enums\TraderOrderTimeLimitType;
use App\Models\TraderOrder;

class TimeLimitService
{
    /**
     * Set a time limit for the given TraderOrder.
     *
     * @param  string  $effectiveAt  - Format: 'Y-m-d H:i:s', timezone: UTC
     * @param  int  $defaultValue  - minutes of hours
     */
    private function setTimeLimit(TraderOrder $traderOrder, int $type, string $effectiveAt, int $defaultValue): void
    {
        $traderOrder->timeLimits()->create([
            'type' => $type,
            'effective_at' => $effectiveAt,
            'default_value' => $defaultValue,
        ]);
    }

    /**
     * Set the contract sign time limit.
     *
     * @param  string  $effectiveAt  - Format: 'Y-m-d H:i:s', timezone: UTC
     * @param  int  $defaultValue  - minutes of hours
     */
    public function setContractSignTimeLimit(TraderOrder $traderOrder, string $effectiveAt, int $defaultValue): void
    {
        $this->setTimeLimit($traderOrder, TraderOrderTimeLimitType::ContractSignTimeLimit, $effectiveAt, $defaultValue);
    }

    /**
     * Set the delivery confirmation time limit.
     *
     * @param  string  $effectiveAt  - Format: 'Y-m-d H:i:s', timezone: UTC
     * @param  int  $defaultValue  - minutes of hours
     */
    public function setDeliveryConfirmationTimeLimit(TraderOrder $traderOrder, string $effectiveAt, int $defaultValue): void
    {
        $this->setTimeLimit($traderOrder, TraderOrderTimeLimitType::DeliveryConfirmationTimeLimit, $effectiveAt, $defaultValue);
    }
}
