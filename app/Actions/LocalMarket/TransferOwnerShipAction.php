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
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('TransferOwnerShipAction not found local market order with reference => ' . $reference, [
                'reference' => $reference,
            ]);
            throw new \Exception('LocalMarketOrder not found with reference: '.$reference);
        }

    }
}
