<?php

namespace App\Actions;

use App\Actions\Contracts\FinancingOrderActivityUpdate;
use App\Jobs\FinancingOrders\FinancingOrderActivityUpdateJob;
use App\Models\FinancingOrder;
use Illuminate\Support\Traits\Localizable;

class FinancingOrderActivityUpdateAction implements FinancingOrderActivityUpdate
{
    use Localizable;

    /**
     * Update the latest activity (Quietly) for a financing order
     */
    public function updateFinancingOrderLatestActivity(FinancingOrder $financingOrder): void
    {
        dispatch(new FinancingOrderActivityUpdateJob($financingOrder))->afterCommit();
    }
}
