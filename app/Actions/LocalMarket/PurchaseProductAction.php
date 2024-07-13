<?php

namespace App\Actions\LocalMarket;

use App\Jobs\UpdateOwnershipJob;
use App\Services\LocalMarketService;
use Illuminate\Support\Facades\DB;

class PurchaseProductAction
{
    private $localMarketService;

    private $usedUnits = [];

    private $usedInventories = [];

    private $remainingAmount;

    public function __construct(LocalMarketService $localMarketService)
    {
        $this->localMarketService = $localMarketService;
    }

    public function handle($financialOrder, $companyId, $preferredTypes, $loanAmount, $rotations, array $usedInventories = [], array $usedUnitsIDs = [])
    {
        $inventory = $this->localMarketService->getInventory($preferredTypes, $loanAmount, $usedInventories);
        if (empty($inventory)) {
            return false;
        }
        $suitableUnits = $this->localMarketService->getSuitableUnits($companyId, $inventory, $loanAmount, $usedUnitsIDs, $rotations);
        $this->usedUnits[] = $suitableUnits['availableUnits'];
        $this->usedInventories[] = $inventory;
        $this->remainingAmount = $suitableUnits['remainingLoan'];

        $usedUnitsIDs[] = $this->generateUnitIDs($suitableUnits['availableUnits']);
        if (empty($suitableUnits['remainingLoan'])) {
            return $this->bulkInsertUnits($financialOrder, $preferredTypes, $companyId, $this->usedUnits, $this->usedInventories);
        }

        $usedInventories[] = $inventory->id;

        return $this->handle($financialOrder, $companyId, $preferredTypes, $this->remainingAmount, $rotations, $usedInventories, $usedUnitsIDs);
    }

    private function bulkInsertUnits($financialOrder, $preferredTypes, $companyId, $units, $usedInventories)
    {
        $unitIds = collect($units)->flatten()->pluck('id')->toArray();

        $this->associateUnitsWithInventories($usedInventories, $units);

        $unitsSql = implode(',', $unitIds);
        DB::transaction(function () use ($financialOrder, $companyId, $unitsSql, $preferredTypes, $usedInventories, &$orderId) {
            $this->localMarketService->updateInventoryUnitsStatus($unitsSql);
            $orderId = $this->localMarketService->createOrder($financialOrder, $preferredTypes, $companyId);
            $this->processUsedInventories($usedInventories, $orderId, $companyId);
        });

        return response()->json([
            'local_market_order_id' => $orderId,
            'order' => $financialOrder,
            'products' => $usedInventories,
            'success' => true,
        ]);
    }

    protected function associateUnitsWithInventories(&$usedInventories, $units)
    {
        foreach ($usedInventories as $i => $inventory) {
            $inventory->units = $units[$i];
        }
    }

    protected function processUsedInventories($usedInventories, $orderId, $companyId)
    {
        foreach ($usedInventories as $inventory) {
            $item = $this->localMarketService->findCommodityItem($inventory->commodity_item_id);
            $inventoryId = $this->localMarketService->createOrderInventory($orderId, $inventory, $item);

            $this->localMarketService->insertOrderUnits($inventory->units, $inventoryId);
            $this->localMarketService->updateInventoryUnitCounts($inventory, count($inventory->units));
            //run job to update ownership of used untis
            UpdateOwnershipJob::dispatch($inventory, $companyId);
        }
    }

    private function generateUnitIDs($usedUnits)
    {
        return collect($usedUnits)->pluck('id')->toArray();
    }
}
