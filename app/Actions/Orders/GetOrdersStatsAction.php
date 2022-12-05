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
            'total_orders' => $this->baseQuery()->count(),
            'total_cancelled_orders' => $this->baseQuery()->cancelled()->count(),
            'total_active_orders' => $this->baseQuery()->active()->count(),
            'total_require_action_orders' => $this->baseQuery()->requireAction()->count(),
            'total_completed_orders' => $this->baseQuery()->completed()->count(),
        ];
    }

    protected function baseQuery()
    {
        return FinancingOrder::when(
            $this->creator, function ($query) {
                $query->byCreator($this->creator);
            }
        );
    }
}
