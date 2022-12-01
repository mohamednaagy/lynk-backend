<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\Wakala\GetLenderWakalaText;
use App\Models\FinancingOrder;

class GetLenderWakalaTextAction implements GetLenderWakalaText
{
    public function handle(FinancingOrder $financingOrder, string $lenderTemplate)
    {
        $date = now()->toDateString();
        $time = now()->toTimeString();
        $commodityNumber = $financingOrder->reference_number;
        $amount = $financingOrder->amount;
        $orderNumber = $financingOrder->id;
        $orderDate = $financingOrder->created_at->format('Y-m-d');

        $template = str_replace([
            '{{signingContractDate}}',
            '{{signingContractTime}}',
            '{{commodityNumber}}',
            '{{amount}}',
            '{{orderNumber}}',
            '{{orderDate}}',
        ], [
            $date,
            $time,
            $commodityNumber,
            $amount,
            $orderNumber,
            $orderDate,
        ], $lenderTemplate);

        return $template;
    }
}
