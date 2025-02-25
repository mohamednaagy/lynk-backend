<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\SellConfirmation;
use App\Enums\LocalMarket\OrderStatus;
use App\Models\TraderOrder;
use App\Traits\LocalMarket\LocalMarketTrait;

class SellConfirmationAction implements SellConfirmation
{
    use LocalMarketTrait;

    public function handle(TraderOrder $reference): void
    {
        $localMarketOrder = $this->getLocalMarketOrderByReference($reference);
        $localMarketOrder->changeStatusTo(OrderStatus::PendingSellCommodities);
    }
}
