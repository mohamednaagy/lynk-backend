<?php

namespace App\Actions;

use App\Actions\Contracts\FinancingOrderActivityUpdate;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;

class FinancingOrderActivityUpdateAction implements FinancingOrderActivityUpdate
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
