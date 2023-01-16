<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\SendSmsWhenStatusIsMurabahaSaleCompleted;
use App\Enums\ClientMessage;
use App\Models\FinancingOrder;
use App\Support\Sms\Sms;

class SendSmsWhenStatusIsMurabahaSaleCompletedAction implements SendSmsWhenStatusIsMurabahaSaleCompleted
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void
    {
        $sellingPrice = $financingOrder->selling_price ?? '';
        $phoneNumber = ltrim($financingOrder->getPhoneNumber()->formatE164(), '+');
        $locale = app()->getLocale();

        Sms::send(
            __(ClientMessage::MurabahaSaleCompleted, [
                'product' => $product,
                'quantity' => $quantity,
                'amount' => $sellingPrice,
            ], $locale), $phoneNumber
        );
    }
}
