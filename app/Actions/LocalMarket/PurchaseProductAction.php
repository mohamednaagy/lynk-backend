<?php

namespace App\Actions\LocalMarket;

use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Exceptions\LocalMarket\PurchaseProductException;
use App\Services\LocalMarketService;
use Illuminate\Support\Facades\DB;

class PurchaseProductAction
{
    private $localMarketService;

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
    public function handle(array $inventories): bool
    {
        DB::beginTransaction();

        try {
            $inventoryDetails = $this->extractDataFromInventories($inventories);

            $this->localMarketService->changeUnitsStatus($inventoryDetails['unitIds'], LocalMarketInventoryUnitsStatus::Reserved);
            $this->localMarketService->refreshInventoryStockQuantities($inventoryDetails['inventoriesIds']);
            // TODO add inventories to local trader order (local_market_order_has_inventories)
            // TODO add units to local trader order (local_market_order_has_units)
            $this->localMarketService->changeUnitsOwnerShip($inventoryDetails['unitIds'], 'current_owner', 1, 'previous_owner', 2);

            DB::commit();

            return true;
        } catch (PurchaseProductException $e) {
            DB::rollBack();
            throw new PurchaseProductException('Failed to purchase product: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Extracts data from inventories.
     *
     * @param  array  $inventories  The inventories to extract data from.
     * @return array The extracted data.
     */
    private function extractDataFromInventories(array $inventories): array
    {
        $inventoriesIds = collect($inventories)->pluck('inventoryId')->toArray();
        $unitIds = collect($inventories)->flatMap(function ($inventory) {
            return collect($inventory['availableUnits'])->pluck('id');
        })->toArray();

        return ['inventoriesIds' => $inventoriesIds, 'unitIds' => $unitIds];
    }
}
