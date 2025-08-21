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

            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('FindEligibleCommoditiesAction Duration', $localMarketOrder) , [
                'localMarketOrderId' => $localMarketOrder->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLocalMarketOrderTitle('Error in FindEligibleCommoditiesAction', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $localMarketOrder->update([
                'status' => OrderStatus::FailedPurchase,
            ]);
        }
    }
}
