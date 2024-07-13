<?php

namespace App\Actions\LocalMarket;

use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Exceptions\LocalMarket\PurchaseProductException;
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

    /**
     * Executes the purchase product action.
     *
     * @param  mixed  $traderOrder  The trader order details.
     * @param  array  $inventories  The inventories to be updated.
     * @return bool Indicates if the operation was successful.
     *
     * @throws PurchaseProductException If the purchase operation fails.
     */
    public function handle($traderOrder, array $inventories): bool
    {
        DB::beginTransaction();

        try {
            // Change units status to be reserved
            $unitIds = $this->extractUnitIds($inventories);
            $this->localMarketService->changeUnitsStatus($unitIds, LocalMarketInventoryUnitsStatus::Reserved);

            // Update inventory available quantity
            $this->localMarketService->recalculateAvailableInventoryQuantities($inventories);

            // Insert ownership for units
            $this->localMarketService->changeUnitsOwnerShip($unitIds, 'current_owner', 'previous_owner', 1, 2);

            DB::commit();

            return true;
        } catch (PurchaseProductException $e) {
            DB::rollBack();
            throw new PurchaseProductException('Failed to purchase product: '.$e->getMessage(), 0, $e);
        }
    }

    private function extractUnitIds($inventories)
    {
        $unitIds = collect($inventories)->flatMap(function ($inventory) {
            return collect($inventory['availableUnits'])->pluck('id');
        })->toArray();

        return $unitIds;
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
