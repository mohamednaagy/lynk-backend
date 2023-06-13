<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\Wakala\GetClientWakalaText;
use App\Enums\BursamProductCode;
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
        $orderNumber = $financingOrder->id;
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
                '{{orderNumber}}',
                '{{orderDate}}',
            ],
            [
                $date,
                $time,
                $commodityNumber,
                $amount,
                in_array($commodity, BursamProductCode::getValues())
                    ? BursamProductCode::fromValue($commodity)->description
                    : $commodity,
                $commodityPrice,
                $clientName,
                $clientNationalId,
                $orderNumber,
                $orderDate,
            ],
            $clientTemplate
        );
    }
}
