<?php

namespace App\Services;

use App\Models\FinancingOrder;

class FinancingOrderActivityUpdateService
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
    private function getLatestActivityDescription(FinancingOrder $financingOrder): string
    {
        return $financingOrder->status->isNot(\App\Enums\FinancingOrderStatus::InProgress)
            || \is_null($financingOrder->current_step)
            ? $financingOrder->status->description
            : $financingOrder->current_step->description;
    }
}
