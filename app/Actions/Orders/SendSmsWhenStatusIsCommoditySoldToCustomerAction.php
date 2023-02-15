<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Enums\ClientMessage;
use App\Models\FinancingOrder;
use App\Support\Sms\Sms;
use Illuminate\Support\Facades\Config;

class SendSmsWhenStatusIsCommoditySoldToCustomerAction implements SendSmsWhenStatusIsCommoditySoldToCustomer
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void
    {
        $phoneNumber = ltrim($financingOrder->getPhoneNumber()->formatE164(), '+');
        $message = $this->resolveSmsMessage($financingOrder, $product, $quantity);

        Sms::send(
            $message,
            $phoneNumber
        );
    }

    private function resolveSmsMessage(FinancingOrder $financingOrder, $product, $quantity)
    {
        $uom = $financingOrder->activeTraderOrder()
            ->first()->uom ?? '';
        $sellingPrice = optional($financingOrder->selling_price)->formatByDecimal() ?? '';
        $query = ['o' => $financingOrder->id];
        $host = Config::get('app.frontend_url.client');
        $url = $host.'/?'.http_build_query($query);
        $locale = app()->getLocale();

        if ($financingOrder->is_verification_required) {
            return __(ClientMessage::CommoditySoldToCustomer, [
                'product' => $product,
                'orderId' => $financingOrder->id,
                'companyName' => $financingOrder->company->name,
                'quantity' => $quantity,
                'uom' => $uom,
                'sellingPrice' => $sellingPrice,
                'url' => $url,
            ], $locale);
        }

        return __(ClientMessage::CommoditySoldToCustomerWithoutVerification, [
            'product' => $product,
            'orderId' => $financingOrder->id,
            'companyName' => $financingOrder->company->name,
            'quantity' => $quantity,
            'uom' => $uom,
            'sellingPrice' => $sellingPrice,
        ], $locale);
    }
}
