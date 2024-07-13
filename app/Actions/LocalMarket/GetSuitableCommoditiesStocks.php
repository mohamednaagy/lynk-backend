<?php

namespace App\Actions\LocalMarket;

use App\Jobs\UpdateOwnershipJob;
use App\Services\LocalMarketService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetSuitableCommoditiesStocks
{
    private $localMarketService;

    private $loanUnits = [];

    private $loanInventories = [];

    public function __construct(LocalMarketService $localMarketService)
    {
        $this->localMarketService = $localMarketService;
    }

    public function handle($companyId, $loanAmount, $preferredTypes = [])
    {
        return $this->getSuitableStocks($companyId, $loanAmount, $preferredTypes);
    }

    private function getSuitableStocks($companyId, $loanAmount, $preferredTypes = [], array $loanInventories = [])
    {
        $inventory = $this->localMarketService->getInventory($loanAmount, $preferredTypes, $loanInventories);
        if (empty($inventory)) {
            Log::info('getSuitableStocksdetails: No suitable inventory found for the loan amount', ['company_id' => $companyId, 'loan_amount' => $loanAmount, 'preferred_types' => $preferredTypes, 'used_inventories' => $loanInventories]);

            return false;
        }

        $suitableUnits = $this->localMarketService->getSuitableUnitsFromInventory($companyId, $inventory, $loanAmount);
        $this->loanUnits[] = $suitableUnits['availableUnits'];
        $this->loanInventories[] = $inventory;

        if (empty($suitableUnits['remainingLoan'])) {
            return [
                'units' => $this->loanUnits,
                'inventories' => $this->loanInventories,
                'can_continue_with_loan' => ($suitableUnits['remainingAmount'] == 0),
            ];
        }

        return $this->getSuitableStocks($companyId, $suitableUnits['remainingAmount'], $preferredTypes, $loanInventories);
    }

    // private function bulkInsertUnits($financialOrder, $preferredTypes, $companyId, $units, $loanInventories)
    // {
    //     $unitIds = collect($units)->flatten()->pluck('id')->toArray();

    //     $this->associateUnitsWithInventories($loanInventories, $units);

    //     $unitsSql = implode(',', $unitIds);
    //     DB::transaction(function () use ($financialOrder, $companyId, $unitsSql, $preferredTypes, $loanInventories, &$orderId) {
    //         $this->localMarketService->updateInventoryUnitsStatus($unitsSql);
    //         $orderId = $this->localMarketService->createOrder($financialOrder, $preferredTypes, $companyId);
    //         $this->processloanInventories($loanInventories, $orderId, $companyId);
    //     });

    //     return response()->json([
    //         'local_market_order_id' => $orderId,
    //         'order' => $financialOrder,
    //         'products' => $loanInventories,
    //         'success' => true
    //     ]);
    // }

    protected function associateUnitsWithInventories(&$loanInventories, $units)
    {
        foreach ($loanInventories as $i => $inventory) {
            $inventory->units = $units[$i];
        }
    }

    protected function processloanInventories($loanInventories, $orderId, $companyId)
    {
        foreach ($loanInventories as $inventory) {
            $item = $this->localMarketService->findCommodityItem($inventory->commodity_item_id);
            $inventoryId = $this->localMarketService->createOrderInventory($orderId, $inventory, $item);

            $this->localMarketService->insertOrderUnits($inventory->units, $inventoryId);
            $this->localMarketService->updateInventoryUnitCounts($inventory, count($inventory->units));
            //run job to update ownership of used untis
            UpdateOwnershipJob::dispatch($inventory, $companyId);
        }
    }
}
