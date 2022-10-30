<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrdersStats;
use App\Models\FinancingOrder;

class GetOrdersStatsAction implements GetOrdersStats
{
    /**
     * @param  \App\Models\Company  $company
     * @return mixed
     */
    public function handle()
    {
        return [
            'total_orders' => FinancingOrder::count(),
            'total_cancelled_orders' => FinancingOrder::canceled()->count(),
            'total_active_orders' => FinancingOrder::active()->count(),
            'total_completed_orders' => FinancingOrder::completed()->count(),
        ];
    }
}
