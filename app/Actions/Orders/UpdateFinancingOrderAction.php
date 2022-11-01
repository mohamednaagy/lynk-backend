<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\UpdateFinancingOrder;
use App\Models\FinancingOrder;
use Illuminate\Support\Arr;
use Propaganistas\LaravelPhone\PhoneNumber;

class UpdateFinancingOrderAction implements UpdateFinancingOrder
{
    /**
     * @param  \App\Models\FinancingOrder  $financingOrder
     * @param  mixed  $data
     * @return mixed
     */
    public function update(FinancingOrder $financingOrder, array $data): FinancingOrder
    {
        $data['phone_number'] = PhoneNumber::make($data['phone_number'], $data['phone_country_code']);

        $financingOrder->update(
            Arr::only($data, [
                'reference_number',
                'national_id',
                'phone_number',
                'amount',
                'selling_price',
            ]
            )
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
