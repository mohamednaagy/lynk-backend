<?php

namespace App\Services\LocalMarket;

use App\Models\LocalMarketOrder;
use App\Models\LocalMarketOrderHasInventory;
use App\Support\DataTransferObjects\LocalMarket\OrderCommoditiesDto;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
            'commodity_type_id' => $inventory->commodity_type_id,
        ]);
    }

    public function insertOrderUnits(LocalMarketOrder $localMarketOrder)
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');

        DB::table('local_market_order_has_units')->insertUsing(
            [
                'unit_id',
                'inventory_id',
                'local_market_order_id',
                'created_at',
                'updated_at',
            ],
            DB::table('local_market_inventory_units')
                ->select(
                    'id',
                    'local_market_inventory_id',
                    DB::raw("{$localMarketOrder->id}"),
                    // 'local_market_inventory_id as local_market_inventory_id',
                    DB::raw("'{$timestamp}' as created_at"),
                    DB::raw("'{$timestamp}' as updated_at")
                )
                ->where('hold_for', $localMarketOrder->id)
        );
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
