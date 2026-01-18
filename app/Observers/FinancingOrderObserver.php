<?php

namespace App\Observers;

use App\Enums\FinancingOrderStatus;
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
        $financingOrder->latest_activity = $this->getLatestActivityDescription($financingOrder);
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

    /**
     * Helper function to get the latest activity description for a financing order
     */
    public function updateLatestActivity(FinancingOrder $financingOrder): void
    {
        $financingOrder->latest_activity = $this->getLatestActivityDescription($financingOrder);
        $financingOrder->saveQuietly();
    }

    /**
     * Get the latest activity description based on status and current step
     */
    private function getLatestActivityDescription(FinancingOrder $financingOrder): string
    {
        return $financingOrder->status->isNot(FinancingOrderStatus::InProgress)
            || \is_null($financingOrder->current_step)
            ? $financingOrder->status->description
            : $financingOrder->current_step->description;
    }
}
