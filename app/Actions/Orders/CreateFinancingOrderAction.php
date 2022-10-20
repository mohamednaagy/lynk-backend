<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Models\FinancingOrder;
use Illuminate\Support\Arr;

class CreateFinancingOrderAction implements CreateFinancingOrder
{
    public function handle(array $data): FinancingOrder
    {
        $financingOrder = FinancingOrder::create(Arr::only($data, ['national_id', 'amount', 'selling_price', 'status']));

        if (isset($data['contract'])) {
            $financingOrder->addMedia($data['contract'])
                ->toMediaCollection('contract');
        }

        if (isset($data['power_of_attorney'])) {
            $financingOrder->addMedia($data['power_of_attorney'])
                ->toMediaCollection('power_of_attorney');
        }

        return $financingOrder;
    }
}
