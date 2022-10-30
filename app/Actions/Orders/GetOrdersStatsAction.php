<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrdersStats;
use App\Models\Company;

class GetOrdersStatsAction implements GetOrdersStats
{
    /**
     * @param  \App\Models\Company  $company
     * @return mixed
     */
    public function handle(Company $company)
    {
        return [
            'total_orders' => $company->orders()->count(),
            'total_cancelled_orders' => $company->orders()->canceled()->count(),
            'total_active_orders' => $company->orders()->active()->count(),
            'total_completed_orders' => $company->orders()->completed()->count(),
        ];
    }
}
