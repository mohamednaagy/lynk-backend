<?php

namespace App\Observers;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCancelled;
use App\Enums\FinancingOrderStatus;
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
     * Handle the TraderOrder "created" event.
     *
     * @return void
     */
    public function created(TraderOrder $traderOrder)
    {
        if ($traderOrder->needsProcessingAfterInitiation()) {
            $traderOrder->processInitiatedTraderOrder();
        }
    }

    /**
     * Determine if the given order should have the current trader order set as the base trader order.
     */
    protected function shouldSetAsBaseTraderOrder(FinancingOrder $order): bool
    {
        if ($order->traderOrders()->count() === 0) {
            return true;
        }

        $baseTraderOrder = $order->traderOrders()
            ->where('is_base', true)
            ->latest('id')
            ->first();

        return $baseTraderOrder && now()->diffInHours($baseTraderOrder->created_at) >= 72;
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
            if ($traderOrder->status->is(TraderOrderStatus::Completed) && $traderOrder->order->company->isCompanyHasMurabahaAutoCompleteOrder()) {
                $this->applyOrderFees($traderOrder);
                if (
                    TraderOrder::whereId($traderOrder->id)->completedWithContractSignedType()->exists() &&
                    $traderOrder->order->company->isCompanyHasMurabahaAutoCompleteOrder()) {
                    $traderOrder->order->update(['status' => FinancingOrderStatus::Completed]);
                }
            }
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
