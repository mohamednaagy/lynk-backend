<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\OrderStatus;
use App\Enums\LocalMarketOrderStatus;
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

        // TODO naser double check local_market_inventory_id relation

        // this error occur when there is an issue here double check it

        // investigate why refund order cost is called
        // [2024-08-28 17:11:13] local.ERROR: App\Listeners\RefundOrderCost::resolveRefundReason(): Argument #1 ($baseTraderOrder) must be of type App\Models\TraderOrder, null given, called in /opt/homebrew/var/www/lynks/lynk-backend/app/Listeners/RefundOrderCost.php on line 47 {"exception":"[object] (TypeError(code: 0): App\\Listeners\\RefundOrderCost::resolveRefundReason(): Argument #1 ($baseTraderOrder) must be of type App\\Models\\TraderOrder, null given, called in /opt/homebrew/var/www/lynks/lynk-backend/app/Listeners/RefundOrderCost.php on line 47 at /opt/homebrew/var/www/lynks/lynk-backend/app/Listeners/RefundOrderCost.php:58)
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

    public function cancelOrder(LocalMarketOrder $localMarketOrder)
    {
        $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::PendingCancellation);
    }
}
