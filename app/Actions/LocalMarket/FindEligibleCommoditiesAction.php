<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Enums\LocalMarketOrderStatus;
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
            abort(500);
            // TODO naser amount is not real amount ouble check it
            $startTime = microtime(true);
            $eligibleCommodities = $this->LoanService->getCommoditiesForLoan(
                $localMarketOrder->id,
                $localMarketOrder->company_id,
                $localMarketOrder->amount,
                $localMarketOrder->preferred_commodity_type
            );

            if ($eligibleCommodities['isLoanCovered']) {
                $localMarketOrder->update([
                    'status' => LocalMarketOrderStatus::EligibleCommoditiesAvailable,
                    'data' => $eligibleCommodities,
                ]);
            } else {
                $localMarketOrder->update([
                    'status' => LocalMarketOrderStatus::NoEligibleCommoditiesAvailable,
                    'data' => $eligibleCommodities,
                ]);
                $localMarketOrder->inverntoryUnits()->update([
                    'hold_for' => null,
                    'status' => InventoryUnitsStatus::Free,
                ]);
            }

            Log::info('FindEligibleCommoditiesAction Duration', [
                'order_id' => $localMarketOrder->id,
                'start_time' => $startTime,
                'end_time' => microtime(true),
                'duration' => convertMicrotimeToDuration(microtime(true) - $startTime),
            ]);
        } catch (\Exception $e) {
            $localMarketOrder->update([
                'status' => LocalMarketOrderStatus::FailedPurchase,
                'comment' => $e->getMessage(),
            ]);

            Log::error('Error in FindEligibleCommoditiesAction', [
                'order_id' => $localMarketOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
