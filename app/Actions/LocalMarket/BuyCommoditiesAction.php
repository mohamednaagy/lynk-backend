<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\BuyCommodities;
use App\Enums\LocalMarketOrderStatus;
use App\Exceptions\LocalMarket\PurchaseProductException;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use App\Services\LocalMarket\UnitService;
use Illuminate\Support\Facades\Log;

class BuyCommoditiesAction implements BuyCommodities
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
            if ($this->LoanService->buyCommodities($localMarketOrder)) {
                $localMarketOrder->update([
                    'status' => LocalMarketOrderStatus::CommoditiesPurchased,
                    'data' => array_merge($localMarketOrder->data, ['data' => UnitService::getUnitsByGroupedByPreviousOwner($localMarketOrder)]),
                ]);

                Log::channel('local_market')->info('unit service for order '.$localMarketOrder->id, ['data' => UnitService::getUnitsByGroupedByPreviousOwner($localMarketOrder)]);
            } else {
                $localMarketOrder->update([
                    'status' => LocalMarketOrderStatus::FailedPurchase,
                ]);
            }

            Log::channel('local_market')->info('BuyCommoditiesAction Duration', [
                'order_id' => $localMarketOrder->id,
                'start_time' => $startTime,
                'end_time' => microtime(true),
                'duration' => convertMicrotimeToDuration(microtime(true) - $startTime),
            ]);
        } catch (\Exception $e) {
            Log::channel('local_market')->error('Error in BuyCommoditiesAction', [
                'order_id' => $localMarketOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $localMarketOrder->update([
                'status' => LocalMarketOrderStatus::FailedPurchase,
            ]);
            throw $e;
        }
    }
}
