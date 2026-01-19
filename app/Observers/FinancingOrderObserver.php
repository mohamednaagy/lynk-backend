<?php

namespace App\Observers;

use App\Contracts\Services\FinancingOrder\FinancingOrderActivityUpdateInterface;
use App\Jobs\General\ProcessInProgressOrder;
use App\Models\FinancingOrder;
use App\Services\AdminOrderAssignmentService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class FinancingOrderObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the FinancingOrder "saving" event.
     */
    public function saving(FinancingOrder $financingOrder): void
    {
        if ($financingOrder->isDirty('status')) {
            $financingOrder->latest_activity = app(FinancingOrderActivityUpdateInterface::class)
                ->getLatestActivityDescription($financingOrder);
        }
    }

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

        // Record initial status history
        $financingOrder->statusHistories()->create([
            'status' => $financingOrder->status,
            'creator_id' => auth()?->id(),
        ]);
    }

    /**
     * Handle the TraderOrder "updated" event.
     *
     * @return void
     */
    public function updated(FinancingOrder $financingOrder)
    {
        if ($financingOrder->status !== $financingOrder->getOriginal('status')
            && FinancingOrder::readyForProcessing()->exists()) {
            ProcessInProgressOrder::dispatch($financingOrder->id);
        }
    }
}
