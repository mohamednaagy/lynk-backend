<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Enums\LocalMarket\OrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Exception;
use Illuminate\Support\Facades\Log;

class FindEligibleCommoditiesAction implements FindEligibleCommodities
{
    public function __construct(
        private LoanService $loanService
    ) {}

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
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

            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('FindEligibleCommoditiesAction Duration', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
