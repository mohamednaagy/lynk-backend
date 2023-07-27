<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\SendSmsWhenStatusIsCommoditySoldToCustomer;
use App\Enums\ClientMessage;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Sms\Sms;
use Illuminate\Support\Facades\Config;
use Shivella\Bitly\Facade\Bitly;

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

        $documentMediaUrl = $this->getMediaUrl($traderOrder);

        if ($financingOrder->is_verification_require) {
            return $this->resolveMessageIfVerificationRequired($financingOrder, $products, $url, $sellingPrice, $documentMediaUrl);
        }

        return $this->resolveMessageIfNoVerificationRequired($financingOrder, $products, $sellingPrice, $documentMediaUrl);
    }

    private function resolveMessageIfVerificationRequired(FinancingOrder $financingOrder, $products, $url, $sellingPrice, $documentMediaUrl)
    {
        return __(ClientMessage::CommoditySoldToCustomer, [
            'products' => $this->getProductsDescription($products),
            'company_name' => $financingOrder->company->name,
            'selling_price' => $sellingPrice,
            'order_id' => $financingOrder->id,
            'url' => $url,
            'document_media_url' => $documentMediaUrl,
        ]);
    }

    public function resolveMessageIfNoVerificationRequired(FinancingOrder $financingOrder, $products, $sellingPrice, $documentMediaUrl)
    {
        return __(ClientMessage::CommoditySoldToCustomerWithoutVerification, [
            'products' => $this->getProductsDescription($products),
            'order_id' => $financingOrder->id,
            'company_name' => $financingOrder->company->name,
            'selling_price' => $sellingPrice,
            'document_media_url' => $documentMediaUrl,
        ]);
    }

    private function getProductsDescription($products)
    {
        return collect($products)
            ->map(function ($product) {
                return "{$product['product']} ({$product['quantity']} {$product['uom']})";
            })->implode(', ');
    }

    private function getMediaUrl($traderOrder)
    {
        $documentMediaUrl = $traderOrder->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer)?->file_url;

        $documentMediaShortUrl = $documentMediaUrl;
        if (app()->isProduction() && ! empty($documentMediaUrl)) {
            $documentMediaShortUrl = Bitly::getUrl($documentMediaUrl);
        }

        return $documentMediaShortUrl;
    }
}
