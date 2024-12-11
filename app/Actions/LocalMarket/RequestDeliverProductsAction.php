<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\RequestDeliverProducts;
use App\Actions\Contracts\LocalMarket\TransferOwnerShip;
use App\Models\LocalMarketOrder;
use Illuminate\Support\Facades\Log;

class RequestDeliverProductsAction implements RequestDeliverProducts
{
    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        try {
            app(TransferOwnerShip::class)->handle($localMarketOrder);
            Log::channel('local_market')->info("Delivery requested for Trader Order ID: {$localMarketOrder->id}");
        } catch (\Exception $e) {
            Log::error("Failed to request delivery for Trader Order ID: {$localMarketOrder->id}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
