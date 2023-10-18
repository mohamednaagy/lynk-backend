<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\SendSmsWhenStatusIsMurabahaSaleCompleted;
use App\Enums\ClientMessage;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Sms\Sms;
use Shivella\Bitly\Facade\Bitly;

class SendSmsWhenStatusIsMurabahaSaleCompletedAction implements SendSmsWhenStatusIsMurabahaSaleCompleted
{
    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void
    {
        if (! $this->isNotifyBorrowersAboutOrderUpdatesOn($financingOrder) || ! $financingOrder->getPhoneNumber()) {
            return;
        }

        $phoneNumber = ltrim($financingOrder->getPhoneNumber()->formatE164(), '+');
        $message = $this->resolveMessage($financingOrder, $traderOrder);

        Sms::send($message, $phoneNumber);
    }

    public function resolveMessage(FinancingOrder $financingOrder, TraderOrder $traderOrder)
    {
        $locale = app()->getLocale();
        $products = $traderOrder->products;
        $amount = $financingOrder->amount?->formatByDecimal() ?? '';
        $documentUrl = $this->getMediaUrl($traderOrder);

        return __(ClientMessage::MurabahaSaleCompleted, [
            'products' => $this->getProductsDescription($products),
            'amount' => $amount,
            'company_name' => $financingOrder->company->name,
            'document_url' => $documentUrl,
        ], $locale);
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
        $warrantyDocumentMediaFile = match ($traderOrder->provider) {
            'dmcc', 'fake' => TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
            'bursam' => TraderOrderMediaCollection::BursamTtiHoldingCertificate,
        };

        $documentUrl = $traderOrder->getFirstMedia($warrantyDocumentMediaFile)?->file_url;

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
