<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketOrder;
use App\Settings\Classes\LocalMurabahaSettings;
use Carbon\Carbon;
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
        $numberOfRotation = app(LocalMurabahaSettings::class)->default_trade_order_rotation_count;
        LocalMarketInventoryUnits::where('local_market_inventory_id', $inventory->id)
            ->where('status', InventoryUnitsStatus::Free)
            ->when($numberOfRotation > 0, function ($query) use ($localMarketOrder, $numberOfRotation) {
                $query->where(function ($subQuery) use ($localMarketOrder, $numberOfRotation) {
                    $companyId = $localMarketOrder->company_id;
                    $subQuery->whereNull('previous_company_id_owners');
                    $jsonConditions = implode(
                        ' OR ',
                        array_map(
                            fn ($index) => "JSON_UNQUOTE(JSON_EXTRACT(extracted.previous_company_id_owners, '$[$index]')) = ?",
                            range(0, $numberOfRotation - 1)
                        )
                    );
                    $bindings = array_fill(0, $numberOfRotation, $companyId);
                    // Add NOT EXISTS condition
                    $subQuery->orWhereRaw("
                NOT EXISTS (
                    SELECT 1
                    FROM local_market_inventory_units AS extracted
                    WHERE $jsonConditions
                )
            ", $bindings);
                });
            })
            ->limit($numberOfNeededUnits)
            ->update([
                'hold_for' => $localMarketOrder->id,
                'status' => InventoryUnitsStatus::Reserved,
            ]);

        $inventory->refreshStockQuantities();
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

    public function changeOrderUnitsOwnershipTo(LocalMarketOrder $localMarketOrder, $ownerType, $ownerIdentifier)
    {
        $localMarketOrder->inventoryUnits()->chunkById(100, function ($units) use ($ownerType, $ownerIdentifier) {
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
