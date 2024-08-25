<?php

namespace App\Services;

use App\Enums\LocalMarket\OrderStatus;
use App\Enums\Trader;
use App\Models\LocalMarketOrder;
use App\Models\LocalMarketOrderHasInventory;
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

    public function insertOrderUnits(LocalMarketOrder $localMarketOrder, array $units)
    {
        $chunks = array_chunk($units, 3000);
        foreach ($chunks as $chunk) {
            $insertData = array_map(function ($unit) {
                return [
                    'inventory_unit_id' => $unit['id'],
                    'order_has_inventory_id' => $unit['local_market_inventory_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $chunk);
            DB::table('local_market_order_has_units')->insert($insertData);
        }
    }

    public function changeOrderStatus($order, $status)
    {
        $order->status = $status;
        $order->save();
    }
}
