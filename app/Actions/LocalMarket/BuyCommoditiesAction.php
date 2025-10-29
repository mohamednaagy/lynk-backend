<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\BuyCommodities;
use App\Enums\LocalMarket\OrderStatus;
use App\Exceptions\LocalMarket\PurchaseProductException;
use App\Jobs\LocalMarket\CompletePurchasing;
use App\Jobs\LocalMarket\InsertOrderInventoriesAndUnits;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Illuminate\Support\Facades\Bus;
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
            Bus::chain([
                new InsertOrderInventoriesAndUnits($localMarketOrder->id),
                new CompletePurchasing($localMarketOrder->id),
            ])
                ->dispatch();

            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('BuyCommoditiesAction Duration', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
                'status' => $localMarketOrder->status,
                'duration' => convertMicrotimeToDuration(microtime(true) - $startTime),
            ]);
        } catch (\Exception $e) {
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLocalMarketOrderTitle('Error at BuyCommoditiesAction', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $localMarketOrder->update([
                'status' => OrderStatus::FailedPurchase,
            ]);
            throw $e;
        }
    }
}
