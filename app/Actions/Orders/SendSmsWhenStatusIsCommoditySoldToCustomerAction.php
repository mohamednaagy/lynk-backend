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
        $message = $this->smsMessageDependsOnOrderVerification($financingOrder, $product, $quantity);

        Sms::send(
            $message,
            $phoneNumber
        );
    }

    private function smsMessageDependsOnOrderVerification(FinancingOrder $financingOrder, $product, $quantity)
    {
        $sellingPrice = optional($financingOrder->selling_price)->getAmount() ?? '';
        $url = Config::get('app.url');
        $locale = app()->getLocale();

        if ($financingOrder->is_verification_required) {
            return __(ClientMessage::CommoditySoldToCustomer, [
                'product' => $product,
                'orderId' => $financingOrder->id,
                'companyName' => $financingOrder->company->name,
                'quantity' => $quantity,
                'sellingPrice' => $sellingPrice,
                'url' => $url.'/'.$financingOrder->id,
            ], $locale);
        }

        return __(ClientMessage::CommoditySoldToCustomerWithoutVerification, [
            'product' => $product,
            'orderId' => $financingOrder->id,
            'companyName' => $financingOrder->company->name,
            'quantity' => $quantity,
            'sellingPrice' => $sellingPrice,
        ], $locale);
    }
}
