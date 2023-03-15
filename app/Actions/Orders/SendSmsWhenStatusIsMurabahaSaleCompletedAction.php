<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\SendSmsWhenStatusIsMurabahaSaleCompleted;
use App\Enums\ClientMessage;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Sms\Sms;

class SendSmsWhenStatusIsMurabahaSaleCompletedAction implements SendSmsWhenStatusIsMurabahaSaleCompleted
{
    public function handle(FinancingOrder $financingOrder, TraderOrder $traderOrder): void
    {
        $phoneNumber = ltrim($financingOrder->getPhoneNumber()->formatE164(), '+');
        $message = $this->resolveMessage($financingOrder, $traderOrder);

        Sms::send($message, $phoneNumber);
    }

    public function resolveMessage(FinancingOrder $financingOrder, TraderOrder $traderOrder)
    {
        $locale = app()->getLocale();
        $products = $traderOrder->products;
        $sellingPrice = $financingOrder->selling_price ?? '';

        return __(ClientMessage::MurabahaSaleCompleted, [
            'product' => $this->getProductsDescription($products),
            'amount' => $sellingPrice,
            'company_name' => $financingOrder->company->name,
        ], $locale);
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
