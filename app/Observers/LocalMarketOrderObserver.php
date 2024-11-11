<?php

namespace App\Observers;

use App\Enums\LocalMarketOrderStatus;
use App\Jobs\LocalMarket\states\CancelledOrderStatus;
use App\Jobs\LocalMarket\states\CommoditiesPurchaseCompletedStatus;
use App\Jobs\LocalMarket\states\EligibleCommoditiesFoundStatus;
use App\Jobs\LocalMarket\states\FailedCancelOrderStatus;
use App\Jobs\LocalMarket\states\FailedPurchaseStatus;
use App\Jobs\LocalMarket\states\FailedSoldOrderStatus;
use App\Jobs\LocalMarket\states\NoEligibleCommoditiesAvailableStatus;
use App\Jobs\LocalMarket\states\PendingCancelOrderStatus;
use App\Jobs\LocalMarket\states\PendingSellOrderStatus;
use App\Jobs\LocalMarket\states\SoldOrderSuccessStatus;
use App\Jobs\LocalMarket\states\TransferCommodityToCustomerStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Support\Str;

class LocalMarketOrderObserver
{
    use LocalMarketHelperTrait;

    public function creating(LocalMarketOrder $localMarketOrder)
    {
        $localMarketOrder->order_no = Str::uuid();
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

        // TODO:sell_commodity_21_10 => add new job for sold success
        // TODO:sell_commodity_21_10 => add new job for cancelled success
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
            case LocalMarketOrderStatus::PendingCancellation:
                dispatch(new PendingCancelOrderStatus($localMarketOrder));
                break;
            case LocalMarketOrderStatus::Cancelled:
                dispatch(new CancelledOrderStatus($localMarketOrder));
                break;
            case LocalMarketOrderStatus::FailedToCancel:
                dispatch(new FailedCancelOrderStatus($localMarketOrder));
                break;
            case LocalMarketOrderStatus::PendingSellCommodities:
                dispatch(new PendingSellOrderStatus($localMarketOrder));
                break;
            case LocalMarketOrderStatus::CommoditiesSell:
                dispatch(new SoldOrderSuccessStatus($localMarketOrder));
                break;
            case LocalMarketOrderStatus::FailedSell:
                dispatch(new FailedSoldOrderStatus($localMarketOrder));
                break;
            case LocalMarketOrderStatus::TransferOwnershipToCustomer:
                dispatch(new TransferCommodityToCustomerStatus($localMarketOrder));
                break;
        }
    }
}
