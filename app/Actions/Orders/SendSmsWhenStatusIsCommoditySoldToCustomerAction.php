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
        if (! $this->isNotifyBorrowersAboutOrderUpdatesOn($financingOrder) || ! $financingOrder->getPhoneNumber()) {
            return;
        }

        $phoneNumber = ltrim($financingOrder->getPhoneNumber()->formatE164(), '+');
        $message = $this->resolveSmsMessage($financingOrder, $traderOrder);

        Sms::send($message, $phoneNumber);
    }

    private function resolveSmsMessage(FinancingOrder $financingOrder, TraderOrder $traderOrder)
    {
        $sellingPrice = optional($financingOrder->selling_price)->convertAndFormatByDecimal() ?? '';

        $query = ['o' => $financingOrder->id];
        $host = Config::get('app.frontend_url.client');
        $url = $host.'/?'.http_build_query($query);
        $products = $traderOrder->products;

        $documentUrl = $this->getMediaUrl($traderOrder);

        if ($financingOrder->is_verification_require) {
            return $this->resolveMessageIfVerificationRequired($financingOrder, $products, $url, $sellingPrice, $documentUrl);
        }

        return $this->resolveMessageIfNoVerificationRequired($financingOrder, $products, $sellingPrice, $documentUrl);
    }

    private function resolveMessageIfVerificationRequired(FinancingOrder $financingOrder, $products, $url, $sellingPrice, $documentUrl)
    {
        return __(ClientMessage::CommoditySoldToCustomer, [
            'products' => $this->getProductsDescription($products),
            'company_name' => $financingOrder->company->name,
            'selling_price' => $sellingPrice,
            'order_id' => $financingOrder->id,
            'url' => $url,
            'document_url' => $documentUrl,
        ]);
    }

    public function resolveMessageIfNoVerificationRequired(FinancingOrder $financingOrder, $products, $sellingPrice, $documentUrl)
    {
        return __(ClientMessage::CommoditySoldToCustomerWithoutVerification, [
            'products' => $this->getProductsDescription($products),
            'order_id' => $financingOrder->id,
            'company_name' => $financingOrder->company->name,
            'selling_price' => $sellingPrice,
            'document_url' => $documentUrl,
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
        $documentUrl = $traderOrder->getFirstMedia(TraderOrderMediaCollection::SellingCommodityToCustomer)?->file_url;

        $documentShortUrl = $documentUrl;
        if (app()->isProduction() && ! empty($documentUrl)) {
            $documentShortUrl = Bitly::getUrl($documentUrl);
        }

        return $documentShortUrl;
    }

    private function isNotifyBorrowersAboutOrderUpdatesOn(FinancingOrder $financingOrder): bool
    {
        $company = $financingOrder->company;

        return $company->notify_borrowers_about_order_updates;
    }
}
