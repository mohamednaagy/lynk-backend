<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\Wakala\GetClientWakalaText;
use App\Models\TraderOrder;

class GetClientWakalaTextAction implements GetClientWakalaText
{
    public function handle(TraderOrder $traderOrder, string $clientTemplate)
    {
        $financingOrder = $traderOrder->order;
        $date = now()->toDateString();
        $time = now()->toTimeString();
        $commodityNumber = $financingOrder->reference_number;
        $amount = $traderOrder->amount ?? '';
        $commodity = $traderOrder->product ?? '';
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
