<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\Wakala\GetClientWakalaText;
use App\Models\FinancingOrder;

class GetClientWakalaTextAction implements GetClientWakalaText
{
    public function handle(FinancingOrder $financingOrder, string $clientTemplate)
    {
        $traderDetails = $financingOrder->activeTraderOrder()->first();
        $date = now()->toDateString();
        $time = now()->toTimeString();
        $commodityNumber = $financingOrder->reference_number;
        $amount = collect($traderDetails->products)->pluck('amount')->implode(' , ') ?? '';
        $commodity = collect($traderDetails->products)->pluck('product')->implode(' , ') ?? '';
        $orderNumber = $financingOrder->id;
        $orderDate = $financingOrder->created_at->format('Y-m-d');

        $template = str_replace([
            '{{signingContractDate}}',
            '{{signingContractTime}}',
            '{{commodityNumber}}',
            '{{amount}}',
            '{{commodity}}',
            '{{orderNumber}}',
            '{{orderDate}}',
        ], [
            $date,
            $time,
            $commodityNumber,
            $amount,
            $commodity,
            $orderNumber,
            $orderDate,
        ], $clientTemplate);

        return $template;
    }
}
