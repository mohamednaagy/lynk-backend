<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\CheckOrderSettlement;
use App\Models\LocalMarketInventoryUnits;
use App\Traits\LocalMarket\LocalMarketTrait;
use Illuminate\Support\Facades\Log;

class CheckOrderSettlementAction implements CheckOrderSettlement
{
    use LocalMarketTrait;

    public function handle(string $reference): bool
    {
        $localMarketOrder = $this->getLocalMarketOrderByReference($reference);

        if ($localMarketOrder->isCommoditiesSettled()) {
            return true;
        }

        // Check if any units still belong to this order (soft deletes handled automatically)
        $isSettled = LocalMarketInventoryUnits::where('last_purchasing_order_id', $localMarketOrder->id)
            ->doesntExist();

        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(
            formatLocalMarketOrderTitle('Settlement check completed', $localMarketOrder),
            ['is_settled' => $isSettled]
        );

        if ($isSettled) {
            $localMarketOrder->markAsSettled();
        }

        return $isSettled;
    }
}
