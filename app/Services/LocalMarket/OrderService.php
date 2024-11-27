<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\OrderStatus;
use App\Enums\Trader;
use App\Models\LocalMarketOrder;
use App\Models\LocalMarketOrderHasInventory;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    use LocalMarketHelperTrait;

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
