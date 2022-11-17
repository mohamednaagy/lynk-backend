<?php

namespace App\Observers;

use App\Enums\ClientMessage;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Sms\Sms;
use Illuminate\Support\Facades\Log;

class FinancingOrderObserver
{
    /**
     * Handle the FinancingOrder "updated" event.
     *
     * @param  FinancingOrder  $financingOrder
     * @return void
     */
    public function updated(FinancingOrder $financingOrder)
    {
        $res = match ($financingOrder->status->value) {
            FinancingOrderStatus::CommoditySoldToCustomer => Sms::driver('msegat')->send(
                __(ClientMessage::CommoditySoldToCustomer, [
                    'product' => '',
                    'quantity' => '',
                    'sellingPrice' => $financingOrder->getOriginal('selling_price'),
                    'url' => '',
                ]), $financingOrder->mobileDialingPhoneNumber),
            FinancingOrderStatus::MurabahaSaleCompleted => Sms::driver('msegat')->send(
                __(ClientMessage::MurabahaSaleCompleted, [
                    'product' => '',
                    'quantity' => '',
                    'amount' => $financingOrder->getOriginal('selling_price'),
                ]), $financingOrder->mobileDialingPhoneNumber),
            default => new \ErrorException('Error found'),
        };

        Log::info($res);
    }
}
