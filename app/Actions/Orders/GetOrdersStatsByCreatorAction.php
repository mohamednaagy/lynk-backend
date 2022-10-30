<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrdersStatsByCreator;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;

class GetOrdersStatsByCreatorAction implements GetOrdersStatsByCreator
{
    /**
     * @param  \App\Models\Company  $company
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function handle(Company $company, Model $model)
    {
        return [
            'total_orders' => $company->orders()->byCreator($model)->count(),
            'total_cancelled_orders' => $company->orders()->byCreator($model)->canceled()->count(),
            'total_active_orders' => $company->orders()->byCreator($model)->active()->count(),
            'total_completed_orders' => $company->orders()->byCreator($model)->completed()->count(),
        ];
    }
}
