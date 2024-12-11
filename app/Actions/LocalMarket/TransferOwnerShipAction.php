<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\TransferOwnerShip;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use Illuminate\Support\Facades\Log;

class TransferOwnerShipAction implements TransferOwnerShip
{
    public function __construct(
        LocalMarketOrder $localMarketOrder
    ) {}

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::TransferOwnershipToCustomer);
        Log::channel('local_market')->info("Transfer Ownership to customer status job added to queue local_market. Trader Order ID: {$localMarketOrder->id} with status => ".LocalMarketOrderStatus::TransferOwnershipToCustomer);
    }
}
