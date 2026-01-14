<?php

namespace App\Services\LocalMarket;

use App\Models\LocalMarketInventory;
use App\Models\LocalMarketOrder;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoanService
{
    private UnitService $unitService;

    private InventoryService $inventoryService;

    public function __construct()
    {
        $this->unitService = app(UnitService::class);
        $this->inventoryService = app(InventoryService::class);
    }

    public function getCommoditiesForLoan(LocalMarketOrder $localMarketOrder)
    {

        DB::beginTransaction();
        try {
            $eligibleInventories = $this->inventoryService->findEligibleInventoryForLoan(
                $localMarketOrder
            );

            if (empty($eligibleInventories)) {
                DB::commit();

                return false;
            }

            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('getCommoditiesForLoan Duration', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
            ]);

            $this->updateEligibleQuantities($eligibleInventories, $localMarketOrder->id);

            $units = $this->unitService->getEligibleUnits($localMarketOrder, $eligibleInventories);
            DB::commit();

            return $units;
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('Error in getCommoditiesForLoan', [
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : 'Hidden in production',
            ]);

            throw $e;
        }
    }

    /**
     * Update eligible quantities for multiple inventories using optimized SQL
     *
     * @param  array  $inventoryUpdates  Array of inventory updates with id and numberOfUnits
     * @param  int  $touchedBy  The ID of the local_market_order that touched these inventories
     */
    private function updateEligibleQuantities(array $inventoryUpdates, int $touchedBy): void
    {
        $caseStatements = [];
        $inventoryIds = [];

        foreach ($inventoryUpdates as $update) {
            $inventoryId = $update['id'];
            $numberOfUnits = $update['numberOfUnits'];

            $caseStatements[] = "WHEN inventory_id = {$inventoryId} THEN {$numberOfUnits}";
            $inventoryIds[] = $inventoryId;
        }

        $caseClause = implode(' ', $caseStatements);
        $inventoryIdsList = implode(',', $inventoryIds);

        // make all inventories inactive
        LocalMarketInventory::query()->whereIn('id', $inventoryIds)
            ->update([
                'is_editable' => 0,
            ]);

        $sql = "
            UPDATE local_market_eligible_quantities
            SET
                eligible_quantity = eligible_quantity - CASE
                    {$caseClause}
                    ELSE 0
                END,
                touched_by = {$touchedBy},
                updated_at = NOW()
            WHERE inventory_id IN ({$inventoryIdsList})
        ";

        DB::update($sql);

        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info('Eligible quantities updated successfully', [
            'inventory_ids' => $inventoryIds,
            'touched_by' => $touchedBy,
        ]);
    }

    public function sellCommodities(LocalMarketOrder $localMarketOrder)
    {

        /**
         * TODO:
         * 1- increase units rotations for the company
         * 2- change units ownershop
         * 3- remove hold for from units
         * 4- change unit status to available
         * 4- change order status to commodities sold
         * 5- log the action
         * 6- send notification to the company
         */
        log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('We wll sell your commodities ISA soon', $localMarketOrder), [
            'localMarketOrderId' => $localMarketOrder->id,
        ]);
    }
}
