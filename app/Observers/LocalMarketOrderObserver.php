<?php

namespace App\Observers;

use App\Enums\LocalMarketOrderStatus;
use App\Jobs\LocalMarket\states\CommoditiesPurchaseCompletedStatus;
use App\Jobs\LocalMarket\states\EligibleCommoditiesFoundStatus;
use App\Jobs\LocalMarket\states\FailedPurchaseStatus;
use App\Jobs\LocalMarket\states\NoEligibleCommoditiesAvailableStatus;
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
        $localMarketOrder->order_no = 'LM_'.$localMarketOrder->source.'_'.$localMarketOrder->id;
        $localMarketOrder->saveQuietly();
    }

    /**
     * Handle the LocalMarketOrder "updated" event.
     *
     * @return void
     */
    public function updated(LocalMarketOrder $localMarketOrder)
    {
        switch ($localMarketOrder->status) {
            case LocalMarketOrderStatus::PendingEligibleCommodities:
                dispatch(new PendingEligibleCommoditiesStatus($localMarketOrder));
                break;
            case LocalMarketOrderStatus::EligibleCommoditiesAvailable:
                dispatch(new EligibleCommoditiesFoundStatus($localMarketOrder));
                break;
            case LocalMarketOrderStatus::NoEligibleCommoditiesAvailable:
                dispatch(new NoEligibleCommoditiesAvailableStatus($localMarketOrder));
                break;
            case LocalMarketOrderStatus::CommoditiesPurchased:
                dispatch(new CommoditiesPurchaseCompletedStatus($localMarketOrder));
                break;
            case LocalMarketOrderStatus::FailedPurchase:
                dispatch(new FailedPurchaseStatus($localMarketOrder));
                break;
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
