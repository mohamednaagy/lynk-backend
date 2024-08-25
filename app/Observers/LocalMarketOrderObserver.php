<?php

namespace App\Observers;

use App\Enums\LocalMarketOrderStatus;
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
        match ($localMarketOrder->status) {
            LocalMarketOrderStatus::PendingEligibleCommodities => PendingEligibleCommoditiesStatus::dispatch($localMarketOrder),
            LocalMarketOrderStatus::EligibleCommoditiesFound => EligibleCommoditiesFoundStatus::dispatch($localMarketOrder),
            default => null,
        };
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
