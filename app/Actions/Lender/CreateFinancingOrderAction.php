<?php
namespace App\Actions\Lender;

use App\Actions\Contracts\Lenders\CreateFinancingOrder;
use App\Models\FinancingOrder;
use Illuminate\Support\Arr;

class CreateFinancingOrderAction implements CreateFinancingOrder
{
    public function handle(array $data): FinancingOrder
    {
        $financingOrder = FinancingOrder::create(Arr::except($data, ['contract', 'power_of_attorney']));

        $financingOrder->addMedia($data['contract'])
         ->toMediaCollection('contract');

        $financingOrder->addMedia($data['power_of_attorney'])
         ->toMediaCollection('power_of_attorney');

        return $financingOrder;
    }
}
