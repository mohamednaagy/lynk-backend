<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\TransferOwnerShip;
use App\Enums\LocalMarket\OrderStatus;
use App\Traits\LocalMarket\LocalMarketTrait;

class TransferOwnerShipAction implements TransferOwnerShip
{
    use LocalMarketTrait;

    public function handle(string $reference): void
    {
        $localMarketOrder = $this->getLocalMarketOrderByReference($reference);
        $localMarketOrder->changeStatusTo(OrderStatus::TransferOwnershipToCustomer);
    }
}
