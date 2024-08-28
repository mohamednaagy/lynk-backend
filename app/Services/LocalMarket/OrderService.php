<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\OrderStatus;
use App\Enums\Trader;
use App\Models\LocalMarketOrder;
use App\Models\LocalMarketOrderHasInventory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder($traderOrder, $financialOrder, array $preferredTypes, int $companyId)
    {
        return LocalMarketOrder::create([
            'source' => Trader::Lynk,
            'trader_order_id' => $traderOrder->id,
            'amount' => $financialOrder->amount->convertAndFormatByDecimal(),
            'national_id' => $financialOrder->national_id,
            'customer_name' => $financialOrder->customer_name,
            'preferred_commodity_type' => json_encode($preferredTypes),
            'company_id' => $companyId,
            'comment' => null,
            'status' => OrderStatus::InProgress,
        ]);
    }

    public function createOrderInventory($orderId, $inventory, $item)
    {
        return LocalMarketOrderHasInventory::create([
            'local_market_order_id' => $orderId,
            'local_market_inventory_id' => $inventory->id,
            'quantity' => count($inventory->units),
            'price' => $inventory->max_price,
            'measurement_id' => $item->measurement_id,
            'currency_id' => $item->currency_id,
            'location_id' => $inventory->supplier_location_id,
            'supplier_id' => $inventory->company_id,
            'previous_owner' => '',
            'commodity_item_id' => $inventory->commodity_item_id,
            'commodity_type_id' => $inventory->commodity_type_id,
        ]);
    }

    public function insertOrderUnits(LocalMarketOrder $localMarketOrder, int $numberOfUnits)
    {
        //TODO need to handle this
        $batchSize = 3000;
        $unitsProcessed = 0;

        while ($unitsProcessed < $numberOfUnits) {
            // Fetch the units from the database using offset and limit to avoid duplicates
            $units = DB::table('local_market_inventory_units')
                ->where('hold_for', $localMarketOrder->id)
                ->offset($unitsProcessed) // Use offset to skip already processed units
                ->limit($batchSize)
                ->get(['id', 'local_market_inventory_id']);

            // If no more units are available, break out of the loop
            if ($units->isEmpty()) {
                break;
            }

            // Prepare insert data for the order units
            $insertData = $units->map(function ($unit) {
                return [
                    'inventory_unit_id' => $unit->id,
                    'order_has_inventory_id' => $unit->local_market_inventory_id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            })->toArray();

            // Insert data in bulk into the local_market_order_has_units table
            DB::table('local_market_order_has_units')->insert($insertData);

            // Update the processed units' status to mark them as reserved or processed
            DB::table('local_market_inventory_units')
                ->whereIn('id', $units->pluck('id')->toArray())
                ->update(['status' => InventoryUnitsStatus::Reserved]);

            // Increment the units processed counter
            $unitsProcessed += $units->count();
        }
    }

    public function insertOrderInventories(LocalMarketOrder $localMarketOrder, $inventories)
    {
        foreach ($inventories as $inventory) {
            LocalMarketOrderHasInventory::create(
                [
                    'local_market_order_id' => $localMarketOrder->id,
                    'local_market_inventory_id' => $inventory['inventoryId'],
                    'quantity' => $inventory['numberOfSuitableUnits'],
                    'supplier_id' => $inventory['supplier']['id'],
                    'price' => $inventory['price'],
                ]
            );
        }
    }

    public function changeOrderStatus($order, $status)
    {
        $order->status = $status;
        $order->save();
    }
}
