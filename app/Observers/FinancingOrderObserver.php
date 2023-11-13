<?php

namespace App\Observers;

use App\Jobs\General\ProcessFinancingOrders;
use App\Models\FinancingOrder;

class FinancingOrderObserver
{
    /**
     * Handle the TraderOrder "created" event.
     *
     * @return void
     */
    public function created(FinancingOrder $financingOrder)
    {
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
