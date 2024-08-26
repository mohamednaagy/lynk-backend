<?php

namespace App\Support\Traders\Traits;

use App\Models\LocalMarketOrder;

trait LocalMarketHelperTrait
{
    public function createLocalMarketOrderHistory(LocalMarketOrder $order, int $status): void
    {
        $order->histories()->updateOrCreate(
            [
                'status' => $status,
            ]
        );
    }
}
