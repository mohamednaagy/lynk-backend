<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\TransferOwnerShip;
use App\Enums\LocalMarket\OrderStatus;
use App\Traits\LocalMarket\LocalMarketTrait;
use Illuminate\Support\Facades\Log;

class TransferOwnerShipAction implements TransferOwnerShip
{
    use LocalMarketTrait;

    public function handle(string $reference): void
    {
        $localMarketOrder = $this->getLocalMarketOrderByReference($reference);
        if ($localMarketOrder) {
            $localMarketOrder->changeStatusTo(OrderStatus::TransferOwnershipToCustomer);
        } else {
            Log::channel('local_market')->error('LocalMarketOrder not found', [
                'reference' => $reference,
            ]);
        }

    }
}
