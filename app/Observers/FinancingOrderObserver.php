<?php

namespace App\Observers;

use App\Actions\Contracts\FinancingOrderActivityUpdate;
use App\Enums\FinancingOrderStatus;
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

        // Record initial status history
        $financingOrder->statusHistories()->create([
            'status' => $financingOrder->status,
            'creator_id' => auth()?->id(),
        ]);

        $this->updateFinancingOrderLatestActivity($financingOrder);
    }

    private function updateFinancingOrderLatestActivity(FinancingOrder $financingOrder): void
    {
        app(FinancingOrderActivityUpdate::class)->updateFinancingOrderLatestActivity($financingOrder);
    }

    /**
     * Handle the TraderOrder "updated" event.
     *
     * @return void
     */
    public function updated(FinancingOrder $financingOrder)
    {
        if (
            $financingOrder->status !== $financingOrder->getOriginal('status')
            && $financingOrder->status->is(FinancingOrderStatus::PendingTraderOrder)
            && FinancingOrder::readyForProcessing()->exists()
        ) {
            ProcessInProgressOrder::dispatch($financingOrder->id);
        }

        if ($financingOrder->wasChanged('status')) {
            $this->updateFinancingOrderLatestActivity($financingOrder);
        }
    }
}
