<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\CreateFinancingOrder;
use App\Models\FinancingOrder;
use Illuminate\Support\Arr;
use Propaganistas\LaravelPhone\PhoneNumber;

class CreateFinancingOrderAction implements CreateFinancingOrder
{
    public function handle(array $data): FinancingOrder
    {
        $data['phone_number'] = PhoneNumber::make($data['phone_number'], $data['phone_country_code']);
        $financingOrder = FinancingOrder::create(
            Arr::only($data, [
                'reference_number',
                'national_id',
                'phone_number',
                'amount',
                'selling_price',
                'status',
                'creator_id',
                'creator_type',
            ])
        );

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
