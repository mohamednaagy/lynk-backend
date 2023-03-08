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
    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void
    {
        $phoneNumber = ltrim($financingOrder->getPhoneNumber()->formatE164(), '+');
        $message = $this->resolveSmsMessage($financingOrder, $traderOrder);

        Sms::send($message, $phoneNumber);
    }

    private function resolveSmsMessage(FinancingOrder $financingOrder, TraderOrder $traderOrder)
    {
        $sellingPrice = optional($financingOrder->selling_price)->formatByDecimal() ?? '';

        $query = ['o' => $financingOrder->id];
        $host = Config::get('app.frontend_url.client');
        $url = $host.'/?'.http_build_query($query);
        $products = $traderOrder
            ->first()
            ->products;

        if ($financingOrder->is_verification_require) {
            return $this->productsWithVerificationMessage($financingOrder, $products, $url, $sellingPrice);
        }

        return $this->productsWithOutVerificationMessage($financingOrder, $products, $sellingPrice);
    }

    public function productsWithOutVerificationMessage(FinancingOrder $financingOrder, $products, $sellingPrice)
    {
        $message = '';
        foreach ($products as $product) {
            $message .= __(ClientMessage::CommoditySoldToCustomerWithoutVerification, [
                'product' => $product['product'],
                'order_id' => $financingOrder->id,
                'company_name' => $financingOrder->company->name,
                'quantity' => $product['quantity'],
                'uom' => $product['uom'],
                'selling_price' => $sellingPrice,
            ]);
        }

        return $message;
    }

    private function productsWithVerificationMessage(FinancingOrder $financingOrder, $products, $url, $sellingPrice)
    {
        $message = '';

        foreach ($products as $product) {
            $message .= __(ClientMessage::CommoditySoldToCustomer, [
                'product' => $product['product'],
                'company_name' => $financingOrder->company->name,
                'quantity' => $product['quantity'],
                'uom' => $product['uom'],
                'selling_price' => $sellingPrice,
            ]);
        }

        return $message .= ' '.__(ClientMessage::CommoditySoldToCustomerUrl, [
            'order_id' => $financingOrder->id,
            'url' => $url,
        ]);
    }
}
