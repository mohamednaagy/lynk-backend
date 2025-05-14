<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Enums\LocalMarket\OrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Illuminate\Support\Facades\Log;

class FindEligibleCommoditiesAction implements FindEligibleCommodities
{
    public function __construct(
        private LoanService $LoanService
    ) {}

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        try {
            // TODO hosam handle transaction here and remove it from pending eligible commodities action
            // DB::beginTransaction();

            $startTime = microtime(true);
            $eligibleCommodities = $this->LoanService->getCommoditiesForLoan($localMarketOrder);

            if ($eligibleCommodities) {
                $localMarketOrder->update([
                    'status' => OrderStatus::EligibleCommoditiesAvailable,
                    'data' => ['inventories' => $eligibleCommodities],
                ]);
            } else {
                $localMarketOrder->update([
                    'status' => OrderStatus::NoEligibleCommoditiesAvailable,
                ]);
            }

            Log::channel('local_market')->info('FindEligibleCommoditiesAction Duration', [
                'order_id' => $localMarketOrder->id,
                'start_time' => $startTime,
                'end_time' => microtime(true),
                'duration' => convertMicrotimeToDuration(microtime(true) - $startTime),
            ]);
        } catch (\Exception $e) {
            Log::channel('local_market')->error('Error in FindEligibleCommoditiesAction', [
                'order_id' => $localMarketOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $localMarketOrder->update([
                'status' => OrderStatus::FailedPurchase,
            ]);
        }
    }
}
