<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;

class FindEligibleCommoditiesAction implements FindEligibleCommodities
{
    public function __construct(
        private LoanService $LoanService
    ) {}

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        $eligibleCommodities = $this->LoanService->getCommoditiesForLoan(
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
        }
    }
}
