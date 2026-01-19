<?php

namespace App\Services;

use App\Contracts\Services\FinancingOrderActivityUpdateInterface;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;

class FinancingOrderActivityUpdateService implements FinancingOrderActivityUpdateInterface
{
    /**
     * Update the latest activity for a financing order
     */
    public function updateFinancingOrderLatestActivity(FinancingOrder $financingOrder): void
    {
        $financingOrder->latest_activity = $this->getLatestActivityDescription($financingOrder);
        $financingOrder->saveQuietly();
    }

    /**
     * Get the latest activity description based on status and current step
     */
    public function getLatestActivityDescription(FinancingOrder $financingOrder): string
    {
        return $financingOrder->status->isNot(FinancingOrderStatus::InProgress)
            || is_null($financingOrder->current_step)
            ? $financingOrder->status->description
            : $financingOrder->current_step->description;
    }
}
