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
        $amount = $financingOrder->amount?->formatByDecimal() ?? '';

        return __(ClientMessage::MurabahaSaleCompleted, [
            'products' => $this->getProductsDescription($products),
            'amount' => $amount,
            'company_name' => $financingOrder->company->name,
        ], $locale);
    }

    private function getProductsDescription($products)
    {
        return collect($products)
            ->map(function ($product) {
                return "{$product['product']} ({$product['quantity']} {$product['uom']})";
            })->implode(', ');
    }
}
