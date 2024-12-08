<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Models\Company;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketOrder;
use App\Settings\Classes\Areas\LocalMurabahaSettings;
use Carbon\Carbon;
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
            $inventories[] = $this->buildResponseArray($inventory, $eligibleInventory['numberOfUnits']);
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
            'inventoryId' => $inventory->id,
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
            ->whereNull('hold_for')
            ->where(function ($query) use ($localMarketOrder, $numberOfRotation) {
                if ($numberOfRotation > 0) {
                    $query->whereNull('previous_company_id_owners')->orWhere(function ($subQuery) use ($localMarketOrder, $numberOfRotation) {
                        // Generate JSON_EXTRACT statements dynamically
                        $jsonExtractParts = [];
                        for ($i = 0; $i < $numberOfRotation; $i++) {
                            $jsonExtractParts[] = "JSON_EXTRACT(previous_company_id_owners, '$[$i]')";
                        }
                        // Combine the generated JSON_EXTRACT parts into a JSON_ARRAY
                        $jsonArrayCondition = implode(",\n", $jsonExtractParts);
                        // Add the NOT JSON_OVERLAPS condition
                        $subQuery->whereRaw(
                            "NOT JSON_OVERLAPS(
                        JSON_ARRAY(?),
                        JSON_ARRAY(
                            $jsonArrayCondition
                        )
                    )",
                            [$localMarketOrder->company_id] // Bind company ID dynamically
                        );
                    });
                }
            })
            ->limit($numberOfNeededUnits)
            ->update([
                'hold_for' => $localMarketOrder->id,
                'status' => InventoryUnitsStatus::Reserved,
            ]);
        $inventory->refreshStockQuantities();
    }

    public function countEligibleUnits(Company $company, LocalMarketInventory $inventory)
    {
        return LocalMarketInventoryUnits::where('local_market_inventory_id', $inventory->id)
            ->where('status', InventoryUnitsStatus::Free)
            ->where('hold_for', 0)
            ->where(function ($query) use ($company) {
                $query->whereNull('previous_company_id_owners')
                    ->orWhereRaw('NOT JSON_OVERLAPS(
                            JSON_ARRAY(?),
                            JSON_ARRAY(
                                JSON_EXTRACT(previous_company_id_owners, "$[0]"),
                                JSON_EXTRACT(previous_company_id_owners, "$[1]"),
                                JSON_EXTRACT(previous_company_id_owners, "$[2]"),
                                JSON_EXTRACT(previous_company_id_owners, "$[3]")
                            )
                        )', [$company->id]);
            })
            ->count();
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
