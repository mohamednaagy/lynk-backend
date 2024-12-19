<?php

namespace App\Observers;

use App\Enums\LocalMarket\OrderStatus;
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
        return $this->canMoveToNextStep($localMarketOrder->getOriginal('status'), $localMarketOrder->status, $localMarketOrder);
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
            case OrderStatus::EligibleCommoditiesAvailable:
                EligibleCommoditiesFoundStatus::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::NoEligibleCommoditiesAvailable:
                NoEligibleCommoditiesAvailableStatus::dispatch($localMarketOrder);
                break;
            case OrderStatus::CommoditiesPurchased:
                CommoditiesPurchaseCompletedStatus::dispatch($localMarketOrder);
                break;
            case OrderStatus::FailedPurchase:
                FailedPurchaseStatus::dispatch($localMarketOrder);
                break;
            case OrderStatus::PendingCancellation:
                PendingCancelOrderStatus::dispatch($localMarketOrder);
                break;
            case OrderStatus::Cancelled:
                CancelledOrderStatus::dispatch($localMarketOrder);
                break;
            case OrderStatus::FailedToCancel:
                FailedCancelOrderStatus::dispatch($localMarketOrder);
                break;
            case OrderStatus::PendingSellCommodities:
                PendingSellOrderStatus::dispatch($localMarketOrder);
                break;
            case OrderStatus::CommoditiesSell:
                SoldOrderSuccessStatus::dispatch($localMarketOrder);
                break;
            case OrderStatus::FailedSell:
                FailedSoldOrderStatus::dispatch($localMarketOrder);
                break;
            case OrderStatus::TransferOwnershipToCustomer:
                TransferCommodityToCustomerStatus::dispatch($localMarketOrder);
                break;
        }
    }
}
