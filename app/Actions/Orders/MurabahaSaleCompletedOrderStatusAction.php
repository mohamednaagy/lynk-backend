<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\MurabahaSaleCompletedOrderStatus;
use App\Enums\ClientMessage;
use App\Models\FinancingOrder;
use App\Support\Sms\Sms;

class MurabahaSaleCompletedOrderStatusAction implements MurabahaSaleCompletedOrderStatus
{
    public function handle(FinancingOrder $financingOrder, string $product, string $quantity): void
    {
        if (! $financingOrder->wasChanged(['status'])) {
            return;
        }

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
