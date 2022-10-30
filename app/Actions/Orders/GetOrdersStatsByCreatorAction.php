<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrdersStatsByCreator;
use App\Models\FinancingOrder;
use Illuminate\Database\Eloquent\Model;

class GetOrdersStatsByCreatorAction implements GetOrdersStatsByCreator
{
    /**
     * @param  \App\Models\Company  $company
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function handle(Model $model)
    {
        return [
            'total_orders' => FinancingOrder::byCreator($model)->count(),
            'total_cancelled_orders' => FinancingOrder::byCreator($model)->canceled()->count(),
            'total_active_orders' => FinancingOrder::byCreator($model)->active()->count(),
            'total_completed_orders' => FinancingOrder::byCreator($model)->completed()->count(),
        ];
    }
}
