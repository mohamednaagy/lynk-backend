<?php

namespace App\Observers;

use App\Enums\TraderOrderStatus;
use App\Events\TraderOrderCancelled;
use App\Jobs\FinancingOrders\CompleteOrderJob;
use App\Models\FinancingOrder;
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
        $order = $traderOrder->order;
        if ($this->shouldSetAsBaseTraderOrder($order, $traderOrder)) {
            $traderOrder->update([
                'is_base' => true,
            ]);
        }

        if ($traderOrder->needsProcessingAfterInitiation()) {
            $traderOrder->processInitiatedTraderOrder();
        }
    }

    /**
     * Determine if the given order should have the current trader order set as the base trader order.
     */
    protected function shouldSetAsBaseTraderOrder(FinancingOrder $order): bool
    {
        return $this->isFirstTraderOrder($order)
            || $this->hasDuplicatedBaseTraderOrder($order);
    }

    private function isFirstTraderOrder(FinancingOrder $order): bool
    {
        return $order->traderOrders()->count() === 1;
    }

    private function hasDuplicatedBaseTraderOrder(FinancingOrder $order, int $staleAfterHours = 72): bool
    {
        $currentBaseOrder = $order->traderOrders()
            ->where('is_base', true)
            ->latest('id')
            ->first();

        if (! $currentBaseOrder) {
            return true;
        }

        return now()->diffInHours($currentBaseOrder->created_at) >= $staleAfterHours;
    }

    /**
     * Handle the TraderOrder "updated" event.
     *
     * @return void
     */
    public function updated(TraderOrder $traderOrder)
    {
        if ($traderOrder->wasChanged(['status'])) {
            $this->takeActionsIfStatusWasChanged($traderOrder);
        }
    }

    protected function takeActionsIfStatusWasChanged(TraderOrder $traderOrder): void
    {
        if ($traderOrder->status->is(TraderOrderStatus::Cancelled)) {
            TraderOrderCancelled::dispatch($traderOrder);
        }

        if ($traderOrder->status->is(TraderOrderStatus::Completed)) {
            if ($traderOrder->hasAutoCompleteFinancingOrder()) {
                CompleteOrderJob::dispatch($traderOrder->order->id, []);
            }
        }

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
