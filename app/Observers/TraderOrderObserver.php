<?php

namespace App\Observers;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCancelled;
use App\Enums\TraderOrderStatus;
use App\Events\TraderOrderCancelled;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;

class TraderOrderObserver
{
    /**
     * Handle the TraderOrder "creating" event.
     *
     * @return void
     */
    public function creating(TraderOrder $traderOrder)
    {
        $order = $traderOrder->order;

        if ($this->shouldSetAsBaseTraderOrder($order)) {
            $traderOrder->fill([
                'is_base' => true,
            ]);
        }
    }

    /**
     * Determine if the given order should have the current trader order set as the base trader order.
     */
    protected function shouldSetAsBaseTraderOrder(FinancingOrder $order): bool
    {
        if ($order->traderOrders()->count() === 1) {
            return true;
        }

        $baseTraderOrder = $order->traderOrders()
            ->where('is_base', true)
            ->latest('id')
            ->first();

        return $baseTraderOrder && now()->diffInHours($baseTraderOrder->created_at) >= 72;
    }

    /**
     * Handle the TraderOrder "updating" event.
     *
     * @return void
     */
    public function updating(TraderOrder $traderOrder)
    {
        if (
            $traderOrder->isDirty(['status'])
            && $traderOrder->status->is(TraderOrderStatus::Cancelled)
        ) {
            $traderOrder->fill([
                'cancelled_at' => now(),
            ]);
        }
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
            app(FireWebhookWhenStatusIsCancelled::class)->handle($traderOrder);
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
