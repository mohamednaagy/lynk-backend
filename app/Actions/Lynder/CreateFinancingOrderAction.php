<?php
namespace App\Actions\lender;

use Illuminate\Support\Arr;
use App\Models\FinancingOrder;
use App\Actions\Contracts\lender\CreateFinancingOrder;

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
