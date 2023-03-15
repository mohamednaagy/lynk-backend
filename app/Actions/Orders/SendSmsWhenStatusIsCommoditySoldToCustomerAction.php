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
        $products = $traderOrder->products;

        if ($financingOrder->is_verification_require) {
            return $this->productsWithVerificationMessage($financingOrder, $products, $url, $sellingPrice);
        }

        return $this->productsWithoutVerificationMessage($financingOrder, $products, $sellingPrice);
    }

    public function productsWithoutVerificationMessage(FinancingOrder $financingOrder, $products, $sellingPrice)
    {
        return __(ClientMessage::CommoditySoldToCustomerWithoutVerification, [
            'product' => $this->getProductsDescription($products),
            'order_id' => $financingOrder->id,
            'company_name' => $financingOrder->company->name,
            'selling_price' => $sellingPrice,
        ]);
    }

    private function productsWithVerificationMessage(FinancingOrder $financingOrder, $products, $url, $sellingPrice)
    {
        return __(ClientMessage::CommoditySoldToCustomer, [
            'product' => $this->getProductsDescription($products),
            'company_name' => $financingOrder->company->name,
            'selling_price' => $sellingPrice,
            'order_id' => $financingOrder->id,
            'url' => $url,
        ]);
    }

    private function getProductsDescription($products)
    {
        return collect($products)
            ->map(function ($product) {
                return $product['product']
                    .' '
                    .'('.$product['quantity']
                    .' '.$product['uom']
                    .')';
            })->implode(', ');
    }
}
