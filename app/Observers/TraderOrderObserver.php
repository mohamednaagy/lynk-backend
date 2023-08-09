<?php

namespace App\Observers;

use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCancelled;
use App\Enums\TraderOrderStatus;
use App\Events\OrderCancelled;
use App\Models\TraderOrder;

class TraderOrderObserver
{
    /**
     * Handle the TraderOrder "created" event.
     *
     * @return void
     */
    public function created(TraderOrder $traderOrder)
    {
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
            OrderCancelled::dispatch($traderOrder);
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
