<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\FindEligibleCommodities;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;

class FindEligibleCommoditiesAction implements FindEligibleCommodities
{
    private LoanService $LoanService;

    public function __construct()
    {
        $this->LoanService = new LoanService;
    }

    public function handle(LocalMarketOrder $localMarketOrder, $companyId, $loanAmount, $preferredTypes): void
    {
        $eligibleCommodities = $this->LoanService->getCommoditiesForLoan($companyId, $loanAmount, $preferredTypes);

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
