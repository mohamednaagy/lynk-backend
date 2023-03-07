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
        $sellingPrice = optional($financingOrder->selling_price)->formatByDecimal() ?? '';

        $query = ['o' => $financingOrder->id];
        $host = Config::get('app.frontend_url.client');
        $url = $host.'/?'.http_build_query($query);
        $products = $financingOrder->activeTraderOrder()
            ->first()
            ->products;

        if (count($products) > 1) {
            return $this->multiProductsMessage($financingOrder, $products, $url, $sellingPrice);
        }

        $product = $products[0];

        if ($financingOrder->is_verification_required) {
            return __(ClientMessage::CommoditySoldToCustomer, [
                'product' => $product['product'],
                'order_id' => $financingOrder->id,
                'company_name' => $financingOrder->company->name,
                'quantity' => $product['quantity'],
                'uom' => $product['uom'],
                'selling_price' => $sellingPrice,
                'url' => $url,
            ]);
        }

        return __(ClientMessage::CommoditySoldToCustomerWithoutVerification, [
            'product' => $product['product'],
            'order_id' => $financingOrder->id,
            'company_name' => $financingOrder->company->name,
            'quantity' => $product['quantity'],
            'uom' => $product['uom'],
            'selling_price' => $sellingPrice,
        ]);
    }

    private function multiProductsMessage($financingOrder, $products, $url, $sellingPrice)
    {
        $message = '';
        if ($financingOrder->is_verification_require) {
            foreach ($products as $product) {
                $message .= __(ClientMessage::MultiCommoditySoldToCustomer, [
                    'product' => $product['product'],
                    'company_name' => $financingOrder->company->name,
                    'quantity' => $product['quantity'],
                    'uom' => $product['uom'],
                    'selling_price' => $sellingPrice,
                ]);
            }

            return $message .= ' '.__(ClientMessage::MultiCommoditySoldToCustomerUrl, [
                'order_id' => $financingOrder->id,
                'url' => $url,
            ]);
        }

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
}
