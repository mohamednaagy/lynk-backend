<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\CancelOrder;
use App\Enums\LocalMarket\OrderStatus;
use App\Support\Traders\Drivers\Lynk\Jobs\ProcessLynkConfirmCancelOrderFromLocalMarket;
use App\Traits\LocalMarket\LocalMarketTrait;

class CancelOrderAction implements CancelOrder
{
    use LocalMarketTrait;

    public function handle(string $reference)
    {
        $localMarketOrder = $this->getLocalMarketOrderByReference($reference);
        if ($localMarketOrder) {
            $localMarketOrder->changeStatusTo(OrderStatus::PendingCancellation);
        } else {
            ProcessLynkConfirmCancelOrderFromLocalMarket::dispatch($reference);
        }
    }
}
