<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\CancelOrder;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;

class CancelOrderAction implements CancelOrder
{
    public function __construct() {}

    public function handle(LocalMarketOrder $localMarketOrder)
    {
        $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::PendingCancellation);
    }
}
