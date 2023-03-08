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
        $message = '';
        foreach ($products as $product) {
            $message .= __(ClientMessage::MurabahaSaleCompleted, [
                'product' => $product['product'],
                'quantity' => $product['quantity'],
                'amount' => $sellingPrice,
            ], $locale);
        }

        return $message .= ' '.__(ClientMessage::MurabahaSaleCompletedWillTransfer);
    }
}
