<?php

namespace App\Observers;

use App\Jobs\General\ProcessFinancingOrders;
use App\Models\FinancingOrder;
use App\Models\User;
use App\Services\AdminOrderAssignmentService;

class FinancingOrderObserver
{
    /**
     * Handle the TraderOrder "created" event.
     *
     * @return void
     */
    public function created(FinancingOrder $financingOrder): void
    {
        AdminOrderAssignmentService::assignNextAdminToFinancingOrder($financingOrder);
        // Dispatch the job to process financing orders
        ProcessFinancingOrders::dispatch();
    }

    /**
     * Handle the TraderOrder "updated" event.
     *
     * @return void
     */
    public function updated(FinancingOrder $financingOrder)
    {
        if ($financingOrder->wasChanged(['status'])) {
            ProcessFinancingOrders::dispatch();
        }
    }
}
