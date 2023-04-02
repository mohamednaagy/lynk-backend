<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\Wakala\GetClientWakalaText;
use App\Models\TraderOrder;

class GetClientWakalaTextAction implements GetClientWakalaText
{
    public function handle(TraderOrder $traderOrder, string $clientTemplate)
    {
        $financingOrder = $traderOrder->order;
        $now = now('Asia/Riyadh');
        $date = $now->toDateString();
        $time = $now->toTimeString();
        $amount = $financingOrder->selling_price->formatByDecimal();
        $commodityNumber = $traderOrder->reference;
        $commodity = collect($traderOrder->products)->pluck('product')->implode(' و ') ?? '';
        $commodityPrice = $financingOrder->amount->formatByDecimal();
        $orderDate = $financingOrder->created_at->format('Y-m-d');
        $clientName = $financingOrder->customer_name;
        $clientNationalId = $financingOrder->national_id;

        return str_replace(
            [
                '{{signingContractDate}}',
                '{{signingContractTime}}',
                '{{commodityNumber}}',
                '{{amount}}',
                '{{commodity}}',
                '{{commodityPrice}}',
                '{{clientName}}',
                '{{clientNationalId}}',
                '{{orderDate}}',
            ],
            [
                $date,
                $time,
                $commodityNumber,
                $amount,
                $commodity,
                $commodityPrice,
                $clientName,
                $clientNationalId,
                $orderDate,
            ],
            $clientTemplate
        );
    }
}
