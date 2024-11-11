<?php

namespace App\Services\LocalMarket;

use App\Models\LocalMarketOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OwnershipService
{
    public function changeUnitOwnership(LocalMarketOrder $localMarketOrder, $ownerType, $ownerIdentifier)
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');

        // Process the inventory units in chunks to avoid memory exhaustion.
        DB::table('local_market_inventory_units')
            ->where('hold_for', $localMarketOrder->id)
            ->chunkById(100, function ($inventoryUnits) use ($localMarketOrder, $ownerIdentifier, $ownerType, $timestamp) {
                // Prepare data for bulk insertion.
                $ownershipData = [];
                foreach ($inventoryUnits as $unit) {
                    $ownershipData[] = [
                        'unit_id' => $unit->id,
                        'local_market_order_id' => $localMarketOrder->id,
                        'current_owner' => $ownerIdentifier,
                        'current_owner_type' => $ownerType,
                        'previous_owner' => $unit->current_owner,
                        'previous_owner_type' => $unit->current_owner_type,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }

                // Insert ownership data in bulk.
                DB::table('local_market_unit_ownership')->insert($ownershipData);

                // Update the processed inventory units with the new ownership.
                $unitIds = collect($inventoryUnits)->pluck('id');
                DB::table('local_market_inventory_units')
                    ->whereIn('id', $unitIds)
                    ->update([
                        'current_owner' => $ownerIdentifier,
                        'current_owner_type' => $ownerType,
                        'updated_at' => $timestamp,
                    ]);
            });
    }

    public function swapCurrentOwnerToPreviousOwner(LocalMarketOrder $localMarketOrder)
    {
        DB::transaction(function () use ($localMarketOrder) {
            // Step 1: Retrieve records and swap ownerships, then insert new rows.
            $timestamp = Carbon::now()->format('Y-m-d H:i:s');

            DB::table('local_market_unit_ownership')
                ->where('local_market_order_id', $localMarketOrder->id)
                ->chunkById(100, function ($rows) use ($timestamp) {
                    $newOwnershipData = [];

                    foreach ($rows as $row) {
                        $newOwnershipData[] = [
                            'unit_id' => $row->unit_id,
                            'local_market_order_id' => $row->local_market_order_id,
                            'current_owner' => $row->previous_owner,
                            'current_owner_type' => $row->previous_owner_type,
                            'previous_owner' => $row->current_owner,
                            'previous_owner_type' => $row->current_owner_type,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    }

                    // Batch insert new ownership records.
                    DB::table('local_market_unit_ownership')->insert($newOwnershipData);
                });

            // Step 2: Retrieve the new current owner for the inventory units update.
            $newOwner = DB::table('local_market_unit_ownership')
                ->where('local_market_order_id', $localMarketOrder->id)
                ->latest('created_at') // Get the latest ownership entry.
                ->first(['current_owner', 'current_owner_type']);

            if (! $newOwner) {
                throw new \Exception("New owner not found for order ID {$localMarketOrder->id}");
            }

            // Step 3: Update inventory units with the new owner.
            DB::table('local_market_inventory_units')
                ->where('hold_for', $localMarketOrder->id)
                ->update([
                    'current_owner' => $newOwner->current_owner,
                    'current_owner_type' => $newOwner->current_owner_type,
                    'updated_at' => $timestamp,
                ]);
        });

    }
}
