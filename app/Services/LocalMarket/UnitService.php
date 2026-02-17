<?php

namespace App\Services\LocalMarket;

use App\Enums\ErrorCode;
use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Exceptions\LocalMarket\ErrorPurchasingAtLocalMarket;
use App\Exceptions\LocalMarket\FailedToHoldRequiredUnitsException;
use App\Jobs\LocalMarket\states\ClearEligibleFlagAndRefreshInventory;
use App\Models\Company;
use App\Models\Lender;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketOrder;
use App\Settings\Classes\LocalMurabahaSettings;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnitService
{
    /**
     * Retrieves eligible units from the inventory based on the loan amount and company history.
     */
    public function getEligibleUnits(LocalMarketOrder $localMarketOrder, $eligibleInventories)
    {
        $inventories = [];
        foreach ($eligibleInventories as $eligibleInventory) {
            $inventory = LocalMarketInventory::find($eligibleInventory['id']);
            $inventories[$inventory->id] = $this->buildResponseArray($inventory, $eligibleInventory['numberOfUnits']);
        }

        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('getEligibleUnits Duration', $localMarketOrder), [
            'localMarketOrderId' => $localMarketOrder->id,
        ]);

        return $inventories;
    }

    /**
     * Builds the response array for eligible units.
     */
    private function buildResponseArray(LocalMarketInventory $inventory, int $numberOfUnits): array
    {

        $item = $inventory->item;
        $location = $inventory->location;

        return [
            'item' => [
                'id' => $inventory->commodity_item_id,
                'name' => $item->name,
                'type' => $item->type->name,
                'volume_sellable_unit' => $item->volume_sellable_unit,
            ],
            'currency' => ['id' => $item->currency_id, 'name' => $item->currency->name],
            'measurement' => ['id' => $item->measurement_id, 'name' => $item->measurement->name],
            'commodityType' => ['id' => $item->commodity_type_id, 'name' => $item->type->name],
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'unique_identifier' => $location->unique_identifier,
            ],
            'supplier' => [
                'id' => $location->supplier->id,
                'name' => $location->supplier->name,
            ],
            'price' => $inventory->price(),
            'numberOfSuitableUnits' => $numberOfUnits,
            'totalCost' => $numberOfUnits * $inventory->price(),
        ];
    }

    public function holdEligibleUnits(LocalMarketOrder $localMarketOrder): void
    {
        $eligibleInventories = $localMarketOrder->data['inventories'];
        $inventoryIds = array_keys($eligibleInventories);

        Log::channel('local_market')->info('Time of hold eligible units start at '.now(), [
            'order_id' => $localMarketOrder->id,
            'inventory_ids' => $inventoryIds,
        ]);

        try {
            $this->executeHoldProcedures($localMarketOrder, $eligibleInventories);

            foreach ($inventoryIds as $inventoryId) {
                ClearEligibleFlagAndRefreshInventory::dispatch($localMarketOrder->id, $inventoryId);
            }
        } catch (Exception $e) {
            Log::channel('local_market')->error('Failed to hold eligible units', [
                'order_id' => $localMarketOrder->id,
                'inventory_ids' => $inventoryIds,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        Log::channel('local_market')->info('Hold eligible units completed', [
            'order_id' => $localMarketOrder->id,
            'inventory_ids' => $inventoryIds,
            'time' => now(),
        ]);
    }

    private function executeHoldProcedures(LocalMarketOrder $localMarketOrder, array $eligibleInventories): void
    {
        $maxAttempts = 3;

        foreach ($eligibleInventories as $inventoryId => $data) {
            $heldUnitsCount = 0;
            $requestedUnits = $data['numberOfSuitableUnits'];

            try {
                for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                    $remainingUnits = $requestedUnits - $heldUnitsCount;

                    DB::statement('CALL hold_order_unit(?, ?, ?, ?)', [
                        $localMarketOrder->id,
                        $remainingUnits,
                        $localMarketOrder->company_id,
                        $inventoryId,
                    ]);

                    $heldUnitsCount = LocalMarketInventoryUnits::where('hold_for', $localMarketOrder->id)
                        ->where('local_market_inventory_id', $inventoryId)
                        ->count();

                    if ($heldUnitsCount >= $requestedUnits) {
                        break;
                    }

                    if ($attempt < $maxAttempts) {
                        Log::channel('local_market')->warning('Hold attempt fell short, retrying', [
                            'order_id' => $localMarketOrder->id,
                            'inventory_id' => $inventoryId,
                            'attempt' => $attempt,
                            'requested' => $requestedUnits,
                            'held' => $heldUnitsCount,
                            'remaining' => $requestedUnits - $heldUnitsCount,
                        ]);
                        usleep(300000); // 300ms this under test might be tuned later on
                    }
                }

                if ($heldUnitsCount < $requestedUnits) {
                    throw new FailedToHoldRequiredUnitsException(
                        $requestedUnits,
                        $heldUnitsCount,
                        $inventoryId,
                        $localMarketOrder->id
                    );
                }
            } catch (FailedToHoldRequiredUnitsException $e) {
                throw $e;
            } catch (Exception $e) {
                throw new FailedToHoldRequiredUnitsException(
                    $requestedUnits,
                    $heldUnitsCount,
                    $inventoryId,
                    $localMarketOrder->id,
                    $e->getMessage(),
                    $e,
                );
            }
        }

        Log::channel('local_market')->info('All hold procedures executed successfully', [
            'order_id' => $localMarketOrder->id,
            'inventories_processed' => count($eligibleInventories),
        ]);
    }

    private function old_holdEligibleUnits(LocalMarketOrder $localMarketOrder, LocalMarketInventory $inventory, int $numberOfNeededUnits)
    {
        log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('time of hold eligible units start at ', $localMarketOrder), [
            'localMarketOrderId' => $localMarketOrder->id,
            'inventory_id' => $inventory->id,
        ]);

        // Get eligible unit IDs
        $eligibleUnitIds = $this->getEligibleUnitIds(
            $inventory,
            $localMarketOrder,
            $numberOfNeededUnits
        );

        // Update units if any found
        if ($eligibleUnitIds->count() != $numberOfNeededUnits) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLocalMarketOrderTitle('there is an error while holding eligible units', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
                'inventory_id' => $inventory->id,
                'needed_units' => $numberOfNeededUnits,
                'hold_units' => $eligibleUnitIds->count(),
            ]);

            throw new ErrorPurchasingAtLocalMarket('Error When Purchasing From Local Marker Available Commodity != Eligible Commodity', ErrorCode::LOCAL_MARKET_CANT_PURCHASING);
        } else {
            $this->updateUnitsStatus(
                $eligibleUnitIds,
                $localMarketOrder->id,
                InventoryUnitsStatus::Reserved
            );
            $inventory->refreshStockQuantities(true);
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('Hold eligible units', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
                'inventory_id' => $inventory->id,
                'eligible_units_count' => $eligibleUnitIds->count(),
                'numberOfNeededUnits' => $numberOfNeededUnits,
                'unit_count' => $inventory->units()->count(),
            ]);
        }

        log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('time of hold eligible units end at ', $localMarketOrder), [
            'localMarketOrderId' => $localMarketOrder->id,
            'inventory_id' => $inventory->id,
        ]);
    }

    /**
     * Get IDs of eligible units based on inventory and company history
     */
    private function getEligibleUnitIds(
        LocalMarketInventory $inventory,
        LocalMarketOrder $localMarketOrder,
        int $limit
    ): Collection {
        $data = collect(
            $this->buildEligibleUnitsQuery($inventory->id, $localMarketOrder->company_id)
                ->select('id')
                ->limit($limit)
                ->lockForUpdate()
                ->pluck('id')
        );

        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('getEligibleUnitIds Duration', $localMarketOrder), [
            'localMarketOrderId' => $localMarketOrder->id,
            'inventoryId' => $inventory->id,
        ]);

        return $data;
    }

    /**
     * Update status of selected units
     */
    private function updateUnitsStatus(
        Collection $unitIds,
        int $holdFor,
        string $status,
        int $chunkSize = 100
    ): void {
        try {
            $unitIds->chunk($chunkSize)->each(function ($chunk) use ($holdFor, $status) {
                LocalMarketInventoryUnits::whereIn('id', $chunk)->update([
                    'hold_for' => $holdFor,
                    'last_purchasing_order_id' => $holdFor, // A more permanent way to store the ID, especially for canceled orders.
                    'status' => $status,
                ]);
            });
        } catch (\Exception $e) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('Failed to update unit statuses', [
                'error' => $e->getMessage(),
                'total_units' => $unitIds->count(),
            ]);
            throw $e;
        }
    }

    public static function getUnitsByGroupedByPreviousOwner(LocalMarketOrder $localMarketOrder)
    {
        $ownershipTypeOriginalSupplier = OwnershipTypes::OriginalSupplier;
        $previousOrdersText = trans('local-market.old_request', [], 'ar');

        return LocalMarketInventoryUnits::select(
            'local_market_inventory_units.local_market_inventory_id',
            DB::raw("
            CASE
                WHEN local_market_inventory_units.previous_owner_type = $ownershipTypeOriginalSupplier
                THEN companies.name
                ELSE '$previousOrdersText'
            END AS previous_owner
        "),
            'local_market_inventory_units.previous_owner_type',
            DB::raw('COUNT(*) AS unit_count'),
        )
            ->join('local_market_inventories', 'local_market_inventories.id', '=', 'local_market_inventory_units.local_market_inventory_id')
            ->leftJoin('companies', 'companies.id', '=', 'local_market_inventory_units.previous_owner')
            ->where('local_market_inventory_units.hold_for', $localMarketOrder->id)
            ->groupBy(
                'local_market_inventory_units.local_market_inventory_id',
                'local_market_inventory_units.previous_owner_type',
                'companies.name'
            )
            ->get()
            ->toArray();
    }

    /**
     * Count eligible units for a company in an inventory
     */
    public function countEligibleUnits(Lender $lender, LocalMarketInventory $inventory): int
    {
        log::channel(LOG_CHANNEL_LOCAL_MARKET)->info('time of count eligible units start at inventory_id => '.$inventory->id.' at '.now(), [
            'inventory_id' => $inventory->id,
        ]);

        $count = $this->buildEligibleUnitsCountQuery($inventory->id, $lender->id)->count();

        log::channel(LOG_CHANNEL_LOCAL_MARKET)->info('time of count eligible units end at inventory_id => '.$inventory->id.' at '.now());

        return $count;
    }

    /**
     * Build base query for eligible units based on company and rotation rules
     */
    private function buildEligibleUnitsQuery(int $inventoryId, int $lenderId): \Illuminate\Database\Query\Builder
    {
        $numberOfRotation = app(LocalMurabahaSettings::class)->default_trade_order_rotation_count;
        // The business logic should be stored procedure "hold_order_unit"

        /*
         * DELIMITER //

CREATE PROCEDURE hold_order_unit(
    IN p_order_id INT,
    IN p_number_of_units INT
)
BEGIN
    DECLARE v_local_market_inventory_id INT DEFAULT 8;

    UPDATE local_market_inventory_units_dummy
    SET hold_for = p_order_id
    WHERE local_market_inventory_id = v_local_market_inventory_id
    AND STATUS = 1
    AND hold_for = 0
    AND deleted_at IS NULL
    AND (previous_company_id_owner_0 != 244 OR previous_company_id_owner_0 IS NULL)
    AND (previous_company_id_owner_1 != 244 OR previous_company_id_owner_1 IS NULL)
    AND (previous_company_id_owner_2 != 244 OR previous_company_id_owner_2 IS NULL)
    LIMIT p_number_of_units;

    -- Optional: Return the number of affected rows
    SELECT ROW_COUNT() as units_held;

END //

DELIMITER ;

         *
         *
         *
         *
         *
         *
         */
        $query = DB::table('local_market_inventory_units')
            ->where('local_market_inventory_id', $inventoryId)
            ->where('status', InventoryUnitsStatus::Free)
            ->where('hold_for', 0)
            ->whereNull('deleted_at')
            ->fromRaw('local_market_inventory_units FORCE INDEX (inventory_units_eligibility_index)');

        if ($numberOfRotation > 0) {
            for ($i = 0; $i < $numberOfRotation; $i++) {
                $query->where(function ($query) use ($lenderId, $i) {
                    $query->where("previous_company_id_owner_$i", '!=', $lenderId)
                        ->orWhereNull("previous_company_id_owner_$i");
                });
            }
        }

        return $query;
    }

    private function buildEligibleUnitsCountQuery(int $inventoryId, int $lenderId): \Illuminate\Database\Query\Builder
    {
        $numberOfRotation = app(LocalMurabahaSettings::class)->default_trade_order_rotation_count;

        $baseQuery = DB::table('local_market_inventory_units')
            ->selectRaw('1')
            ->where('local_market_inventory_id', $inventoryId)
            ->where('status', InventoryUnitsStatus::Free)
            ->where('hold_for', 0)
            ->whereNull('deleted_at')
            ->fromRaw('local_market_inventory_units FORCE INDEX (inventory_units_eligibility_index)');

        if ($numberOfRotation > 0) {
            for ($i = 0; $i < $numberOfRotation; $i++) {
                $baseQuery->where(function ($q) use ($lenderId, $i) {
                    $q->where("previous_company_id_owner_$i", '!=', $lenderId)
                        ->orWhereNull("previous_company_id_owner_$i");
                });
            }
        }

        $wrapped = DB::table(DB::raw("({$baseQuery->limit(config('trader.providers.lynk.max_count_eligible_units_per_inventory'))->toSql()}) as t"))
            ->mergeBindings($baseQuery);

        return $wrapped;
    }

    /**
     * Change ownership of order units directly with better performance
     *
     * @param  int|string  $ownerIdentifier
     */
    public function changeOrderUnitsOwnershipTo(
        LocalMarketOrder $localMarketOrder,
        string $ownerType,
        $ownerIdentifier,
        string $action
    ): void {
        try {
            // Single update query for all units with this hold_for
            DB::table('local_market_inventory_units')
                ->where('hold_for', $localMarketOrder->id)
                ->update([
                    'previous_owner' => DB::raw('current_owner'),
                    'previous_owner_type' => DB::raw('current_owner_type'),
                    'current_owner' => $ownerIdentifier,
                    'current_owner_type' => $ownerType,
                    'last_action' => $action,
                    'updated_at' => now(),
                ]);

            log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('Completed ownership change', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
                'owner_type' => $ownerType,
                'action' => $action,
            ]);
        } catch (\Exception $e) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLocalMarketOrderTitle('Failed to change ownership', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function revertInventoryUnitOwnership(LocalMarketOrder $localMarketOrder)
    {
        $ownershipService = app(OwnershipService::class);
        LocalMarketInventoryUnits::where('hold_for', $localMarketOrder->id)
            ->chunkById(100, function ($units) use ($localMarketOrder) {
                log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('Swapping current owner for order ', $localMarketOrder), [
                    'localMarketOrderId' => $localMarketOrder->id,
                ]);

                foreach ($units as $unit) {
                    // Extract the last valid owner details
                    $lastValidOwner = $unit->getLastValidOwner();
                    $newCurrentOwner = $lastValidOwner['current_owner'];
                    $newCurrentOwnerType = $lastValidOwner['current_owner_type'];

                    log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(
                        formatLocalMarketOrderTitle('Swapping unit ID '.$unit->id.
                            ' to owner '.$newCurrentOwner.
                            ' of type '.$newCurrentOwnerType, $localMarketOrder),
                        [
                            'localMarketOrderId' => $localMarketOrder->id,
                            'unit_id' => $unit->id,
                            'new_current_owner' => $newCurrentOwner,
                            'new_current_owner_type' => $newCurrentOwnerType,
                        ]
                    );

                    // Update the unit using Eloquent, which will trigger the observer
                    $unit->update([
                        'current_owner' => $newCurrentOwner,
                        'current_owner_type' => $newCurrentOwnerType,
                        'previous_owner' => $unit->current_owner,
                        'previous_owner_type' => $unit->current_owner_type,
                        'last_action' => UnitOwnershipAction::Cancel,
                    ]);
                }
            });
    }
}
