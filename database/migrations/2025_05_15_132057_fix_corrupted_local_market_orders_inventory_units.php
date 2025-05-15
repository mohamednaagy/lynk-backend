<?php

use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\OrderService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\TraderOrderStatus;
use App\Enums\TraderOrderMode;
use App\Enums\Trader;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $corruptionDate = '2025-05-12';
            Log::channel('local_market')->info('Starting migration to fix corrupted orders and inventory units');

            $orders = LocalMarketOrder::query()
                ->select('local_market_orders.*')
                ->join('trader_orders', 'trader_orders.reference', '=', 'local_market_orders.external_order_no')
                ->join('financing_orders', 'trader_orders.financing_order_id', '=', 'financing_orders.id')
                ->whereIn('trader_orders.status', [TraderOrderStatus::Completed, TraderOrderStatus::Cancelled])
                ->whereDate('trader_orders.created_at', '>=', $corruptionDate)
                ->where('trader_orders.provider', Trader::Lynk)
                ->where('trader_orders.mode', TraderOrderMode::Automatic)
                ->whereIn('local_market_orders.id', function ($query) {
                    $query->select('hold_for')
                        ->from('local_market_inventory_units')
                        ->whereNotNull('hold_for')
                        ->whereColumn('hold_for', 'local_market_orders.id');
                })
                ->whereDoesntHave('orderInventories')
                ->get();

            Log::channel('local_market')->info('Retrieved affected orders', [
                'orders_count' => $orders->count(),
                'order_ids' => $orders->pluck('id')->toArray(),
            ]);

            $orderService = app(OrderService::class);
            $inventoryService = app(InventoryService::class);

            foreach ($orders as $order) {
                try {
                    Log::channel('local_market')->info('Processing order', ['order_id' => $order->id]);

                    $orderService->insertOrderInventories($order);

                    $inventoryService->completeOrderUnits($order);

                    Log::channel('local_market')->info('Completed processing order', ['order_id' => $order->id]);
                } catch (\Throwable $e) {
                    Log::channel('local_market')->error('Error processing order', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            Log::channel('local_market')->info('Migration completed for fixing corrupted orders and inventory units');
        });
    }

    public function down(): void
    {
        // No rollback logic as this is a corrective data fix
    }
};
