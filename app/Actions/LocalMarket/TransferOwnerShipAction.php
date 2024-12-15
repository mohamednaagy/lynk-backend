<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\TransferOwnerShip;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;

class TransferOwnerShipAction implements TransferOwnerShip
{
    public function __construct(
        LocalMarketOrder $localMarketOrder
    ) {}

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::TransferOwnershipToCustomer);
    }
}
