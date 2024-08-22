<?php

namespace App\Actions\LocalMarket;

use App\Services\LocalMarketService;
use Illuminate\Support\Facades\Log;

class GetSuitableCommoditiesStocks
{
    private LocalMarketService $localMarketService;

    private array $loanDetails;

    public function __construct(LocalMarketService $localMarketService)
    {
        $this->localMarketService = $localMarketService;
        $this->loanDetails = ['isLoanCovered' => false];
    }

    public function handle($companyId, $loanAmount, $preferredTypes = [])
    {
        $this->determineSuitableStocks($companyId, $loanAmount, $preferredTypes);

        return $this->loanDetails;
    }

    private function determineSuitableStocks($companyId, $loanAmount, $preferredTypes = [], array $loanInventories = [])
    {
        $inventory = $this->localMarketService->getInventory($loanAmount, $preferredTypes, $loanInventories);

        if (empty($inventory)) {
            Log::info('getSuitableStocksdetails: Cannot process this loan', [
                'company_id' => $companyId,
                'loan_amount' => $loanAmount,
                'preferred_types' => $preferredTypes,
                'loan_details' => $this->loanDetails,
            ]);

            return;
        }

        $inventorySuitableUnits = $this->localMarketService->getSuitableUnitsFromInventory($companyId, $inventory, $loanAmount);
        $this->updateLoanDetails($inventorySuitableUnits);

        if ($this->loanDetails['isLoanCovered']) {
            return;
        }

        // Recursive call to handle remaining loan amount
        $this->determineSuitableStocks($companyId, $inventorySuitableUnits['remainingLoan'], $preferredTypes, $this->loanDetails['inventories_id']);
    }

    private function updateLoanDetails(array $inventorySuitableUnits)
    {
        $this->loanDetails['inventories'][] = $inventorySuitableUnits;
        $this->loanDetails['inventories_id'][] = $inventorySuitableUnits['inventoryId'];
        $this->loanDetails['isLoanCovered'] = $inventorySuitableUnits['isLoanCovered'];
    }
}
