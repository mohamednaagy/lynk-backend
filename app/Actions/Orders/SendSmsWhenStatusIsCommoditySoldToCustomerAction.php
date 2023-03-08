<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Enums\ClientMessage;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Sms\Sms;
use Illuminate\Support\Facades\Config;

class SendSmsWhenStatusIsCommoditySoldToCustomerAction implements SendSmsWhenStatusIsCommoditySoldToCustomer
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void
    {
        $activeTraderOrder = $financingOrder->activeTraderOrder()->first();

        if ($activeTraderOrder === null) {
            return;
        }

        $phoneNumber = ltrim($financingOrder->getPhoneNumber()->formatE164(), '+');
        $message = $this->resolveSmsMessage($financingOrder, $activeTraderOrder, $product, $quantity);

        Sms::send(
            $message,
            $phoneNumber
        );
    }

    private function resolveSmsMessage(FinancingOrder $financingOrder, TraderOrder $activeTraderOrder, $product, $quantity)
    {
        $uom = $activeTraderOrder->uom ?? '';

        $sellingPrice = optional($financingOrder->selling_price)->formatByDecimal() ?? '';

        $query = ['o' => $financingOrder->id];
        $host = Config::get('app.frontend_url.client');
        $url = $host.'/?'.http_build_query($query);

        if ($financingOrder->is_verification_required) {
            return __(ClientMessage::CommoditySoldToCustomer, [
                'product' => $product,
                'order_id' => $financingOrder->id,
                'company_name' => $financingOrder->company->name,
                'quantity' => $quantity,
                'uom' => $uom,
                'selling_price' => $sellingPrice,
                'url' => $url,
            ]);
        }

        return __(ClientMessage::CommoditySoldToCustomerWithoutVerification, [
            'product' => $product,
            'order_id' => $financingOrder->id,
            'company_name' => $financingOrder->company->name,
            'quantity' => $quantity,
            'uom' => $uom,
            'selling_price' => $sellingPrice,
        ]);
    }
}
