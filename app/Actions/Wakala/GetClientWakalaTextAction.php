<?php

namespace App\Actions\Wakala;

use App\Actions\Contracts\Wakala\GetClientWakalaText;
use App\Enums\BursamProductCode;
use App\Enums\FinancingOrderHistory;
use App\Models\TraderOrder;
use Illuminate\Support\Traits\Localizable;

class GetClientWakalaTextAction implements GetClientWakalaText
{
    use Localizable;

    public function handle(TraderOrder $traderOrder, string $clientTemplate)
    {
        $transferOwnershipToLenderDocumentHistory = $traderOrder->traderHistories()
            ->where('action', FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)
            ->first();
        $financingOrder = $traderOrder->order;
        $date = $transferOwnershipToLenderDocumentHistory ? saudi_now('Y-m-d h:i:s A', $transferOwnershipToLenderDocumentHistory->created_at) : null;
        $time = $transferOwnershipToLenderDocumentHistory ? saudi_now('h:i:s A', $transferOwnershipToLenderDocumentHistory->created_at) : null;
        $amount = $financingOrder->selling_price->convertAndFormatByDecimal(separator: ',');
        $commodityNumber = $traderOrder->reference;
        $commodity = collect($traderOrder->products)->pluck('product')->implode(' و ') ?? '';
        $commodityPrice = $financingOrder->amount->convertAndFormatByDecimal(separator: ',');
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
                $this->withLocale('ar', function () use ($commodity) {
                    return in_array($commodity, BursamProductCode::getValues())
                        ? BursamProductCode::fromValue($commodity)->description
                        : $commodity;
                }),
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
