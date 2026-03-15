<?php

namespace App\Actions;

use App\Actions\Contracts\FinancingOrderActivityUpdate;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Localizable;

class FinancingOrderActivityUpdateAction implements FinancingOrderActivityUpdate
{
    use Localizable;

    /**
     * Update the latest activity (Quietly) for a financing order
     */
    public function updateFinancingOrderLatestActivity(FinancingOrder $financingOrder): void
    {
        $financingOrder->refresh();

        $financingOrder->latest_activity = $this->getLatestActivityDescription($financingOrder);
        Log::channel(LOG_CHANNEL_LYNK)
            ->debug('FinancingOrderActivityUpdateAction: updating latest_activity for order', [
                'order_id' => $financingOrder->id,
                'latest_activity' => $financingOrder->latest_activity,
            ]);
        $financingOrder->saveQuietly();
    }

    public function getLatestActivityDescription(FinancingOrder $financingOrder): string
    {
        return $this->withLocale('en', function () use ($financingOrder) {
            return $financingOrder->status->isNot(FinancingOrderStatus::InProgress)
            || is_null($financingOrder->current_step)
                ? $financingOrder->status->description
                : $financingOrder->current_step->description;
        });
    }
}
