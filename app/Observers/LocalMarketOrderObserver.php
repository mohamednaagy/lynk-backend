<?php

namespace App\Observers;

use App\Enums\LocalMarket\OrderStatus;
use App\Jobs\LocalMarket\states\CancelledOrderStatus;
use App\Jobs\LocalMarket\states\CommoditiesPurchaseCompletedStatus;
use App\Jobs\LocalMarket\states\FailedCancelOrderStatus;
use App\Jobs\LocalMarket\states\FailedPurchaseStatus;
use App\Jobs\LocalMarket\states\FailedSoldOrderStatus;
use App\Jobs\LocalMarket\states\HoldEligibleUnitInventoriesJob;
use App\Jobs\LocalMarket\states\InitiateOrderStatus;
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
        if ($localMarketOrder->wasChanged(['status'])) {
            return $this->canMoveToNextStep($localMarketOrder->getOriginal('status'), $localMarketOrder->status, $localMarketOrder);
        }
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
                HoldEligibleUnitInventoriesJob::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::NoEligibleCommoditiesAvailable:
                NoEligibleCommoditiesAvailableStatus::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::CommoditiesPurchased:
                CommoditiesPurchaseCompletedStatus::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::FailedPurchase:
                FailedPurchaseStatus::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::PendingCancellation:
                PendingCancelOrderStatus::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::Cancelled:
                CancelledOrderStatus::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::FailedToCancel:
                FailedCancelOrderStatus::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::PendingSellCommodities:
                PendingSellOrderStatus::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::CommoditiesSell:
                SoldOrderSuccessStatus::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::FailedSell:
                FailedSoldOrderStatus::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::TransferOwnershipToCustomer:
                TransferCommodityToCustomerStatus::dispatch($localMarketOrder->id);
                break;
            case OrderStatus::initiate:
                InitiateOrderStatus::dispatch($localMarketOrder->id);
                break;
        }
    }
}
