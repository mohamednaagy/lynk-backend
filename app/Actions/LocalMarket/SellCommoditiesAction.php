<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\SellCommodities;
use App\Enums\LocalMarketOrderStatus;
use App\Exceptions\LocalMarket\PurchaseProductException;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Illuminate\Support\Facades\Log;

class SellCommoditiesAction implements SellCommodities
{
    public function __construct(
        private LoanService $LoanService
    ) {}

    /**
     * Executes the purchase product action.
     *
     * @param  mixed  $traderOrder  The trader order details.
     * @param  array  $inventories  The inventories to be updated.
     * @return bool Indicates if the operation was successful.
     *
     * @throws PurchaseProductException If the purchase operation fails.
     */
    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        try {
            $startTime = microtime(true);

            $this->LoanService->sellCommodities($localMarketOrder);

            Log::info('SellCommoditiesAction Duration', [
                'order_id' => $localMarketOrder->id,
                'start_time' => $startTime,
                'end_time' => microtime(true),
                'duration' => convertMicrotimeToDuration(microtime(true) - $startTime),
            ]);
        } catch (\Exception $e) {
            $localMarketOrder->update([
                'status' => LocalMarketOrderStatus::FailedSell,
            ]);
            Log::error('Error in SellCommoditiesAction', [
                'order_id' => $localMarketOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
