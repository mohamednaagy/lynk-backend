<?php

namespace App\Observers;

use App\Actions\Contracts\FinancingOrderActivityUpdate;
use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Events\TraderOrderCancelled;
use App\Jobs\FinancingOrders\CompleteOrderJob;
use App\Models\TraderOrder;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class TraderOrderObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the TraderOrder "creating" event.
     *
     * @return void
     */
    public function creating(TraderOrder $traderOrder) {}

    /**
     * Handle the TraderOrder "created" event.
     *
     * @return void
     */
    public function created(TraderOrder $traderOrder)
    {
        if ($traderOrder->needsProcessingAfterInitiation()) {
            $traderOrder->processInitiatedTraderOrder();
        }

        // Update the parent financing order's latest activity
        $this->updateFinancingOrderLatestActivity($traderOrder);
    }

    /**
     * Determine if the given order should have the current trader order set as the base trader order.
     */

    /**
     * Handle the TraderOrder "updated" event.
     */
    public function updated(TraderOrder $traderOrder): void
    {
        if ($traderOrder->wasChanged(['status'])) {
            $this->takeActionsIfStatusWasChanged($traderOrder);
        }

        if (
            $traderOrder->status !== $traderOrder->getOriginal('status')
            || $traderOrder->last_history_action !== $traderOrder->getOriginal('last_history_action')
        ) {
            $this->updateFinancingOrderLatestActivity($traderOrder);
        }
    }

    protected function takeActionsIfStatusWasChanged(TraderOrder $traderOrder): void
    {
        if ($traderOrder->status->is(TraderOrderStatus::Cancelled)) {
            TraderOrderCancelled::dispatch($traderOrder);
        }

        if (
            $traderOrder->status->is(TraderOrderStatus::Completed) &&
            $traderOrder->hasAutoCompleteFinancingOrder() &&
            ! $traderOrder->checkOrderHistoryAction(FinancingOrderHistory::DeliveryConfirmed)
        ) {
            CompleteOrderJob::dispatch($traderOrder->order->id, []);
        }
    }

    /**
     * Update the parent financing order's latest activity
     */
    private function updateFinancingOrderLatestActivity(TraderOrder $traderOrder): void
    {
        app(FinancingOrderActivityUpdate::class)->updateFinancingOrderLatestActivity($traderOrder->order);
    }

    /**
     * Handle the TraderOrder "deleted" event.
     *
     * @return void
     */
    public function deleted(TraderOrder $traderOrder)
    {
        //
    }

    /**
     * Handle the TraderOrder "restored" event.
     *
     * @return void
     */
    public function restored(TraderOrder $traderOrder)
    {
        //
    }

    /**
     * Handle the TraderOrder "force deleted" event.
     *
     * @return void
     */
    public function forceDeleted(TraderOrder $traderOrder)
    {
        //
    }
}
