<?php

namespace App\Services\TraderOrder;

use App\Enums\TraderOrderTimeLimitType;
use App\Models\TraderOrder;

class TraderOrderTimeLimitService
{
    /**
     * Set a time limit for the given TraderOrder.
     *
     * @param TraderOrder $traderOrder
     * @param int $type
     * @param string $effectiveAt
     * @param int $defaultValue
     * @return void
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
     * @param TraderOrder $traderOrder
     * @param string $effectiveAt
     * @param int $defaultValue
     * @return void
     */
    public function setContractSignTimeLimit(TraderOrder $traderOrder, string $effectiveAt, int $defaultValue): void
    {
        $this->setTimeLimit($traderOrder, TraderOrderTimeLimitType::ContractSignTimeLimit, $effectiveAt, $defaultValue);
    }

    /**
     * Set the delivery confirmation time limit.
     *
     * @param TraderOrder $traderOrder
     * @param string $effectiveAt
     * @param int $defaultValue
     * @return void
     */
    public function setDeliveryConfirmationTimeLimit(TraderOrder $traderOrder, string $effectiveAt, int $defaultValue): void
    {
        $this->setTimeLimit($traderOrder, TraderOrderTimeLimitType::DeliveryConfirmationTimeLimit, $effectiveAt, $defaultValue);
    }
}

