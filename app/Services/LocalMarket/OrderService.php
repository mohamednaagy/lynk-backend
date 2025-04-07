<?php

namespace App\Services\LocalMarket;

use App\Models\LocalMarketOrder;
use App\Models\LocalMarketOrderHasInventory;
use App\Support\DataTransferObjects\LocalMarket\OrderCommoditiesDto;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    use LocalMarketHelperTrait;

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
            'commodity_type_id' => $inventory->item->commodity_type_id,
        ]);
    }

    public function insertOrderUnits(LocalMarketOrder $localMarketOrder)
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d H:i:s');
            $batchSize = 1000;
            $totalUnits = DB::table('local_market_inventory_units')
                ->where('hold_for', $localMarketOrder->id)
                ->count();

            DB::table('local_market_inventory_units')
                ->select([
                    'id',
                    'local_market_inventory_id',
                ])
                ->where('hold_for', $localMarketOrder->id)
                ->orderBy('id')
                ->chunk($batchSize, function ($chunk) use ($localMarketOrder, $timestamp) {
                    $insertData = collect($chunk)->map(function ($unit) use ($localMarketOrder, $timestamp) {
                        return [
                            'unit_id' => $unit->id,
                            'inventory_id' => $unit->local_market_inventory_id,
                            'local_market_order_id' => $localMarketOrder->id,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ];
                    })->toArray();

                    DB::table('local_market_order_has_units')->insert($insertData);
                });

            // Verify final count
            $insertedCount = DB::table('local_market_order_has_units')
                ->where('local_market_order_id', $localMarketOrder->id)
                ->count();

            if ($insertedCount !== $totalUnits) {
                throw new Exception(
                    "Units count mismatch. Expected: {$totalUnits}, Inserted: {$insertedCount}"
                );
            }
        } catch (Exception $e) {
            Log::channel('local_market')->error('Error in insertOrderUnits', [
                'order_id' => $localMarketOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function insertOrderInventories(LocalMarketOrder $localMarketOrder)
    {
        $inventories = OrderCommoditiesDto::getInventoriesFromOrder($localMarketOrder);
        foreach ($inventories as $inventory_id => $data) {
            LocalMarketOrderHasInventory::create(
                [
                    'local_market_order_id' => $localMarketOrder->id,
                    'local_market_inventory_id' => $inventory_id,
                    'quantity' => $data['numberOfSuitableUnits'],
                    'supplier_id' => $data['supplier']['id'],
                    'price' => $data['price'],
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
