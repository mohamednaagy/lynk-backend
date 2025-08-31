<?php

namespace App\Observers;

use App\Jobs\General\ProcessInProgressOrder;
use App\Models\FinancingOrder;
use App\Services\AdminOrderAssignmentService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class FinancingOrderObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the TraderOrder "created" event.
     */
    public function created(FinancingOrder $financingOrder): void
    {
        app(AdminOrderAssignmentService::class)->assignNextAdminToFinancingOrder($financingOrder);
        // Dispatch the job to process financing orders
        if (FinancingOrder::readyForProcessing()->exists()) {
            ProcessInProgressOrder::dispatch($financingOrder->id);
        }
    }

    /**
     * Handle the TraderOrder "updated" event.
     *
     * @return void
     */
    public function updated(FinancingOrder $financingOrder)
    {
        if ($financingOrder->wasChanged(['status']) && FinancingOrder::readyForProcessing()->exists()) {
            ProcessInProgressOrder::dispatch($financingOrder->id);
        }
    }
}
