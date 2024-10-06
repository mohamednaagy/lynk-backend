<?php

namespace App\Observers;

use App\Enums\LocalMarketOrderStatus;
use App\Jobs\LocalMarket\states\CommoditiesPurchaseCompletedStatus;
use App\Jobs\LocalMarket\states\EligibleCommoditiesFoundStatus;
use App\Jobs\LocalMarket\states\FailedPurchaseStatus;
use App\Jobs\LocalMarket\states\NoEligibleCommoditiesAvailableStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;

class LocalMarketOrderObserver
{
    use LocalMarketHelperTrait;

    public function creating(LocalMarketOrder $localMarketOrder)
    {
        $localMarketOrder->order_no = 'LM_'.rand(11, 99).time();
    }

    /**
     * Handle the LocalMarketOrder "created" event.
     *
     * @return void
     */
    public function created(LocalMarketOrder $localMarketOrder)
    {
        $this->fireJob($localMarketOrder);

    }

    public function updating(LocalMarketOrder $localMarketOrder)
    {
        return $this->canMoveToNextStep($localMarketOrder->getOriginal('status'), $localMarketOrder->status);

    }

    /**
     * Handle the LocalMarketOrder "updated" event.
     *
     * @return void
     */
    public function updated(LocalMarketOrder $localMarketOrder)
    {
        if ($localMarketOrder->wasChanged(['status'])) {
            $this->fireJob($localMarketOrder);
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

    private function fireJob(LocalMarketOrder $localMarketOrder)
    {
        switch ($localMarketOrder->status) {
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
}
