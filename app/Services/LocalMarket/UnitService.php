<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Models\Company;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketOrder;
use App\Settings\Classes\LocalMurabahaSettings;
use Carbon\Carbon;
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
            $this->holdEligibleUnits($localMarketOrder, $inventory, $eligibleInventory['numberOfUnits']);
            $inventories[$inventory->id] = $this->buildResponseArray($inventory, $eligibleInventory['numberOfUnits']);
        }

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

    private function holdEligibleUnits(LocalMarketOrder $localMarketOrder, LocalMarketInventory $inventory, int $numberOfNeededUnits)
    {
        Log::channel('local_market')->info('time of hold eligible units start at '.now(), [
            'order_id' => $localMarketOrder->id,
            'inventory_id' => $inventory->id,
        ]);

        // Get eligible unit IDs
        $eligibleUnitIds = $this->getEligibleUnitIds(
            $inventory,
            $localMarketOrder->company_id,
            $numberOfNeededUnits
        );

        // Update units if any found
        if ($eligibleUnitIds->isNotEmpty()) {
            $this->updateUnitsStatus(
                $eligibleUnitIds,
                $localMarketOrder->id,
                InventoryUnitsStatus::Reserved
            );
        }

        Log::channel('local_market')->info('time of hold eligible units end at '.now());
        $inventory->refreshStockQuantities();
    }

    /**
     * Get IDs of eligible units based on inventory and company history
     */
    private function getEligibleUnitIds(
        LocalMarketInventory $inventory,
        int $companyId,
        int $limit
    ): Collection {
        return collect(
            $this->buildEligibleUnitsQuery($inventory->id, $companyId)
                ->select('id')
                ->limit($limit)
                ->pluck('id')
        );
    }

    /**
     * Update status of selected units
     */
    private function updateUnitsStatus(
        Collection $unitIds,
        int $holdFor,
        string $status,
        int $chunkSize = 1000
    ): void {
        try {
            DB::beginTransaction();

            $unitIds->chunk($chunkSize)->each(function ($chunk) use ($holdFor, $status) {
                $ids = $chunk->join(',');
                DB::statement("
                    UPDATE local_market_inventory_units 
                    SET hold_for = ?, 
                        status = ?,
                        updated_at = ? 
                    WHERE id IN ({$ids})
                ", [$holdFor, $status, now()]);

                Log::channel('local_market')->info('Updated unit statuses chunk');
            });

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('local_market')->error('Failed to update unit statuses', [
                'error' => $e->getMessage(),
                'total_units' => $unitIds->count(),
            ]);
            throw $e;
        }
    }

    public static function getUnitsByGroupedByPreviousOwner(LocalMarketOrder $localMarketOrder)
    {
        $ownershipTypeOriginalSupplier = OwnershipTypes::OriginalSupplier;
        $previousOrdersText = trans('local-market.old_request', [], 'ar'); // Localized text

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
    public function countEligibleUnits(Company $company, LocalMarketInventory $inventory): int
    {
        Log::channel('local_market')->info('time of count eligible units start at '.now(), [
            'inventory_id' => $inventory->id,
        ]);

        $count = $this->buildEligibleUnitsQuery($inventory->id, $company->id)->count();

        Log::channel('local_market')->info('time of count eligible units end at '.now());

        return $count;
    }

    /**
     * Build base query for eligible units based on company and rotation rules
     */
    private function buildEligibleUnitsQuery(int $inventoryId, int $companyId): \Illuminate\Database\Query\Builder
    {
        $numberOfRotation = app(LocalMurabahaSettings::class)->default_trade_order_rotation_count;

        $query = DB::table('local_market_inventory_units')
            ->where('local_market_inventory_id', $inventoryId)
            ->where('status', InventoryUnitsStatus::Free)
            ->where('hold_for', 0)
            ->whereNull('deleted_at');

        if ($numberOfRotation > 0) {
            for ($i = 0; $i < $numberOfRotation; $i++) {
                $query->where(function ($query) use ($companyId, $i) {
                    $query->where("previous_company_id_owner_$i", '!=', $companyId)
                        ->orWhereNull("previous_company_id_owner_$i");
                });
            }
        }

        return $query;
    }

    public function changeOrderUnitsOwnershipTo(LocalMarketOrder $localMarketOrder, $ownerType, $ownerIdentifier, $action)
    {
        $localMarketOrder->inventoryUnits()->chunkById(100, function ($units) use ($ownerType, $ownerIdentifier, $action) {
            foreach ($units as $unit) {
                // Get the current values for previous_owner and previous_owner_type
                $previousOwner = $unit->current_owner;
                $previousOwnerType = $unit->current_owner_type;

                // Perform update with new and old values using Eloquent's update() method
                $unit->update([
                    'current_owner' => $ownerIdentifier,
                    'current_owner_type' => $ownerType,
                    'previous_owner' => $previousOwner,
                    'previous_owner_type' => $previousOwnerType,
                    'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'action' => $action,
                ]);
            }
        });
    }

    public function revertInventoryUnitOwnership(LocalMarketOrder $localMarketOrder)
    {
        LocalMarketInventoryUnits::where('hold_for', $localMarketOrder->id)
            ->chunkById(100, function ($units) use ($localMarketOrder) {
                Log::channel('local_market')->info('Swapping current owner for order '.$localMarketOrder->id);

                foreach ($units as $unit) {
                    // Extract the last valid owner details
                    $lastValidOwner = $unit->getLastValidOwner();
                    $newCurrentOwner = $lastValidOwner['current_owner'];
                    $newCurrentOwnerType = $lastValidOwner['current_owner_type'];

                    Log::channel('local_market')->info(
                        'Swapping unit ID '.$unit->id.
                            ' to owner '.$newCurrentOwner.
                            ' of type '.$newCurrentOwnerType
                    );

                    // Update the unit using Eloquent, which will trigger the observer
                    $unit->update([
                        'current_owner' => $newCurrentOwner,
                        'current_owner_type' => $newCurrentOwnerType,
                        'previous_owner' => $unit->current_owner,
                        'previous_owner_type' => $unit->current_owner_type,
                    ]);
                }
            });
    }
}
