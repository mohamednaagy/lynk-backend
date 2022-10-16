<?php
namespace App\Actions\Lenders;

use App\Models\FinancingOrder;
use App\Actions\Contracts\Lenders\CreateFinancingOrder;
use Illuminate\Support\Arr;

class CreateFinancingOrderAction implements CreateFinancingOrder
{
    public function handle(array $data): FinancingOrder
    {
        $financingOrder = FinancingOrder::create(Arr::only($data, ['national_id', 'amount', 'selling_price', 'status']));

        $financingOrder->addMedia($data['contract'])
         ->toMediaCollection('contract');

        $financingOrder->addMedia($data['power_of_attorney'])
         ->toMediaCollection('power_of_attorney');

        return $financingOrder;
    }
}
