<?php

namespace App\Actions\LocalMarket;

use App\Services\LocalMarketService;
use Illuminate\Support\Facades\Log;

class GetSuitableCommoditiesStocks
{
    private $localMarketService;

    private $loanDetails = ['isLoanCovered' => false];

    public function __construct(LocalMarketService $localMarketService)
    {
        $this->localMarketService = $localMarketService;
    }

    public function handle($companyId, $loanAmount, $preferredTypes = [])
    {
        $this->getSuitableStocks($companyId, $loanAmount, $preferredTypes);

        return $this->loanDetails;
    }

    private function getSuitableStocks($companyId, $loanAmount, $preferredTypes = [], array $loanInventories = [])
    {
        $inventory = $this->localMarketService->getInventory($loanAmount, $preferredTypes, $loanInventories);

        if (empty($inventory)) {
            Log::info('getSuitableStocksdetails: Cannot processed with this loan', ['company_id' => $companyId, 'loan_amount' => $loanAmount, 'preferred_types' => $preferredTypes, 'loan_details' => $this->loanDetails]);

            return $this->loanDetails;
        }

        $inventorySuitableUnits = $this->localMarketService->getSuitableUnitsFromInventory($companyId, $inventory, $loanAmount);
        $this->loanDetails['inventories'][] = $inventorySuitableUnits;
        $this->loanDetails['inventories_id'][] = $inventorySuitableUnits['inventoryId'];
        $this->loanDetails['isLoanCovered'] = $inventorySuitableUnits['isLoanCovered'];

        if ($this->loanDetails['isLoanCovered']) {
            return $this->loanDetails;
        }

        return $this->getSuitableStocks($companyId, $inventorySuitableUnits['remainingLoan'], $preferredTypes, $this->loanDetails['inventories_id']);
    }
}
