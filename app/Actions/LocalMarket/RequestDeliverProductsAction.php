<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\RequestDeliverProducts;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use Illuminate\Support\Facades\Log;

class RequestDeliverProductsAction implements RequestDeliverProducts
{
    public function __construct() {}

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        try {
            $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::PendingDelivery);
            Log::channel('local_market')->info("Delivery requested for Local Market Order ID: {$localMarketOrder->id}");
        } catch (\Exception $e) {
            Log::channel('local_market')->error("Failed to request delivery for Local Market Order ID: {$localMarketOrder->id}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
