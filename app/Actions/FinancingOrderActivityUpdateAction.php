<?php

namespace App\Actions;

use App\Actions\Contracts\FinancingOrderActivityUpdate;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Support\Traits\Localizable;

class FinancingOrderActivityUpdateAction implements FinancingOrderActivityUpdate
{
    use Localizable;

    /**
     * Update the latest activity (Quietly) for a financing order
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
        return $this->withLocale('en', function () use ($financingOrder) {
            if ($financingOrder->status->is(FinancingOrderStatus::InProgress)) {
                return $financingOrder->status->description;
            }

            return is_null($financingOrder->current_step)
                ? $financingOrder->status->description
                : $financingOrder->current_step->description;
        });
    }
}
