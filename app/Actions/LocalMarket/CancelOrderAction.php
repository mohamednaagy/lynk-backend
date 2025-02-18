<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\CancelOrder;
use App\Enums\LocalMarket\OrderStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Drivers\Lynk\Jobs\ProcessLynkConfirmCancelOrderFromLocalMarket;

class CancelOrderAction implements CancelOrder
{
    public function __construct() {}

    public function handle(string $reference)
    {
        $localMarketOrder = LocalMarketOrder::where('external_order_no', $reference)->first();
        if ($localMarketOrder) {
            $localMarketOrder->changeStatusTo(OrderStatus::PendingCancellation);
        } else {
            ProcessLynkConfirmCancelOrderFromLocalMarket::dispatch($reference);
        }
    }
}
