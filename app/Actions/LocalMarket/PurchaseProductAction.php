<?php

namespace App\Actions\LocalMarket;

use App\Jobs\UpdateOwnershipJob;
use App\Services\LocalMarketService;
use Exception;
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

    public function handle($traderOrder, $financialOrder, $companyId, $preferredTypes, $loanAmount, $rotations, array $usedInventories = [], array $usedUnitsIDs = []): bool
    {
        try {
        $inventory = $this->localMarketService->getInventory($preferredTypes, $loanAmount, $usedInventories);
        if (empty($inventory)) {
            throw new Exception("Loan_amount_can_not_be_fullfilled", 422);
        }
        $suitableUnits = $this->localMarketService->getSuitableUnits($companyId, $inventory, $loanAmount, $usedUnitsIDs, $rotations);
        $this->usedUnits[] = $suitableUnits['availableUnits'];
        $this->usedInventories[] = $inventory;
        $this->remainingAmount = $suitableUnits['remainingLoan'];

        $usedUnitsIDs[] = $this->retrieveUnitIDs($suitableUnits['availableUnits']);
        if (empty($suitableUnits['remainingLoan'])) {
            return $this->bulkInsertUnits($traderOrder, $financialOrder, $preferredTypes, $companyId, $this->usedUnits, $this->usedInventories);
        }

        $usedInventories[] = $inventory->id;
        return $this->handle($traderOrder, $financialOrder, $companyId, $preferredTypes, $this->remainingAmount, $rotations, $usedInventories, $usedUnitsIDs);
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }

    }

    private function bulkInsertUnits($traderOrder, $financialOrder, $preferredTypes, $companyId, $units, $usedInventories)
    {
        $unitIds = $this->retrieveUnitIDs($units);

        $this->associateUnitsWithInventories($usedInventories, $units);

        $unitsSql = implode(',', $unitIds);
        DB::transaction(function () use ($traderOrder, $financialOrder, $companyId, $unitsSql, $preferredTypes, $usedInventories) {
            $this->localMarketService->updateInventoryUnitsStatus($unitsSql);
            $order = $this->localMarketService->createOrder($traderOrder, $financialOrder, $preferredTypes, $companyId);
            $this->processUsedInventories($usedInventories, $order->id, $companyId);
        });

        return true;
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
            $orderInventoryId = $this->localMarketService->createOrderInventory($orderId, $inventory, $item);

            $this->localMarketService->insertOrderUnits($inventory->units, $orderInventoryId->id);
            $this->localMarketService->updateInventoryUnitCounts($inventory, count($inventory->units));
            //run job to update ownership of used untis
            UpdateOwnershipJob::dispatch($inventory, $companyId);
        }
    }

    private function retrieveUnitIDs($usedUnits)
    {
        return collect($usedUnits)->flatten()->pluck('id')->toArray();
    }
}
