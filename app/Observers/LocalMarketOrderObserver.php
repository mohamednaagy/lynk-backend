<?php

namespace App\Observers;

use App\Enums\LocalMarketOrderStatus;
use App\Jobs\LocalMarket\states\CommoditiesPurchaseCompletedStatus;
use App\Jobs\LocalMarket\states\EligibleCommoditiesFoundStatus;
use App\Jobs\LocalMarket\states\PendingEligibleCommoditiesStatus;
use App\Models\LocalMarketOrder;

class LocalMarketOrderObserver
{
    /**
     * Handle the LocalMarketOrder "created" event.
     *
     * @return void
     */
    public function created(LocalMarketOrder $localMarketOrder)
    {
        //
    }

    /**
     * Handle the LocalMarketOrder "updated" event.
     *
     * @return void
     */
    public function updated(LocalMarketOrder $localMarketOrder)
    {
        $job = match ($localMarketOrder->status) {
            LocalMarketOrderStatus::PendingEligibleCommodities => new PendingEligibleCommoditiesStatus($localMarketOrder),
            LocalMarketOrderStatus::EligibleCommoditiesAvailable => new EligibleCommoditiesFoundStatus($localMarketOrder),
            // add job to cancel order and notify user
            LocalMarketOrderStatus::CommoditiesPurchased => new CommoditiesPurchaseCompletedStatus($localMarketOrder),

            default => null,
        };

        if ($job) {
            $job->handle();
        }
    }

    /**
     * Handle the LocalMarketOrder "deleted" event.
     *
     * @return void
     */
    public function deleted(LocalMarketOrder $localMarketOrder)
    {
        //
    }

    /**
     * Handle the LocalMarketOrder "restored" event.
     *
     * @return void
     */
    public function restored(LocalMarketOrder $localMarketOrder)
    {
        //
    }

    /**
     * Handle the LocalMarketOrder "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(LocalMarketOrder $localMarketOrder)
    {
        //
    }
}
