<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Models\TraderOrder;
use Illuminate\Support\Arr;

class UpdateTraderOrderAction implements UpdateTraderOrder
{
    public function handle(TraderOrder $traderOrder, array $data): TraderOrder
    {
        $traderOrder->update(
            Arr::only(
                $data,
                [
                    'product',
                    'quantity',
                    'amount',
                    'currency',
                    'warehouse',
                    'owner',
                    'previous_owner',
                    'new_owner',
                    'date_time_of_purchasing_commodity',
                    'warehouse_or_vault_emirates',
                    'warehouse_or_vault_country',
                    'warehouse_or_vault_operator_id',
                    'warrant_no',
                    'hs_code',
                    'uom',
                    'exchange_rate',
                    'auto_generate_financing_institution_certificate',
                ]
            )
        );

        return $traderOrder;
    }
}
