<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrdersStats;
use App\Models\FinancingOrder;
use Illuminate\Database\Eloquent\Model;

class GetOrdersStatsAction implements GetOrdersStats
{
    protected ?Model $creator = null;

    public function setCreator(Model $creator)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @param  \App\Models\Company  $company
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function handle()
    {
        return [
            'total_orders' => FinancingOrder::byCreator($this->creator)->count(),
            'total_cancelled_orders' => FinancingOrder::byCreator($this->creator)->canceled()->count(),
            'total_active_orders' => FinancingOrder::byCreator($this->creator)->active()->count(),
            'total_completed_orders' => FinancingOrder::byCreator($this->creator)->completed()->count(),
        ];
    }
}
