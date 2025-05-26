<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Enums\LocalMarket\OrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FindEligibleCommoditiesAction implements FindEligibleCommodities
{
    public function __construct(
        private LoanService $loanService
    ) {}

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        DB::beginTransaction();
        try {
            $startTime = microtime(true);
            $eligibleCommodities = $this->loanService->getCommoditiesForLoan($localMarketOrder);

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

            DB::commit();

            Log::channel('local_market')->info('FindEligibleCommoditiesAction Duration', [
                'order_id' => $localMarketOrder->id,
                'duration' => convertMicrotimeToDuration(microtime(true) - $startTime),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

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
