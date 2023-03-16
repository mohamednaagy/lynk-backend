<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Models\TraderOrder;
use Illuminate\Support\Arr;

class UpdateTraderOrderAction implements UpdateTraderOrder
{
    public function handle(TraderOrder $traderOrder, array $data): TraderOrder
    {
        $products = Arr::only(
            $data,
            [
                'products',
                'exchange_rate',
                'auto_generate_financing_institution_certificate',
            ]
        );

        $traderOrder->update($products);

        return $traderOrder;
    }
}
