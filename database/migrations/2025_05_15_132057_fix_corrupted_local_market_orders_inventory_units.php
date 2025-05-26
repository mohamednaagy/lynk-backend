<?php

use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Enums\LocalMarketOrderStatus;
use App\Enums\Trader;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketOrder;
use App\Models\LocalMarketOrderHasInventory;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\OrderService;
use App\Support\DataTransferObjects\LocalMarket\OrderCommoditiesDto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $corruptionDate = '2025-05-12';
        Log::channel('local_market')->info('Starting migration to fix corrupted orders and inventory units');

        $orderIds = LocalMarketOrder::query()
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
            ->pluck('local_market_orders.id');

        Log::channel('local_market')->info('Retrieved affected orders', [
            'orders_count' => count($orderIds),
            'order_ids' => $orderIds,
        ]);

        collect($orderIds)->chunk(10)->each(function ($chunkedIds) {
            $orders = LocalMarketOrder::query()->whereIn('id', $chunkedIds)
                ->get();

            $orderService = app(OrderService::class);
            $inventoryService = app(InventoryService::class);

            foreach ($orders as $order) {
                DB::beginTransaction();

                try {
                    Log::channel('local_market')->info('Processing order', ['order_id' => $order->id]);

                    // Get expected inventories from DTO
                    $expectedInventories = OrderCommoditiesDto::getInventoriesFromOrder($order);
                    $expectedInventoryCount = count($expectedInventories);

                    // Get units currently held for the order
                    $heldUnits = LocalMarketInventoryUnits::where('hold_for', $order->id)
                        ->select(['id', 'status', 'last_completed_order_id', 'previous_company_id_owners'])
                        ->get();

                    $expectedReleaseCount = $heldUnits->count();
                    $unitIds = $heldUnits->pluck('id')->toArray();

                    // Define base query for released units
                    $releasedUnitsQuery = LocalMarketInventoryUnits::query()
                        ->whereIn('id', $unitIds)
                        ->where('hold_for', 0)
                        ->where('last_purchasing_order_id', $order->id)
                        ->where('status', InventoryUnitsStatus::Free);

                    // Execute order logic
                    $orderService->insertOrderInventories($order);

                    if ($order->status === LocalMarketOrderStatus::Cancelled) {
                        $inventoryService->cancelOrderUnits($order);

                        $releasedUnits = $releasedUnitsQuery
                            ->where(function ($query) use ($order) {
                                $query->whereNull('last_completed_order_id')
                                    ->orWhere('last_completed_order_id', '<>', $order->id);
                            })
                            ->get();

                        foreach ($releasedUnits as $unit) {
                            $previousOwners = $unit->previous_company_id_owners ?? [];
                            if (in_array($order->company_id, $previousOwners)) {
                                throw new Exception(
                                    'Rotation is invalid: The owner cannot be assigned as a previous owner of the unit.'
                                );
                            }
                        }

                    } elseif ($order->status === LocalMarketOrderStatus::CommoditiesSell) {
                        $inventoryService->completeOrderUnits($order);

                        $releasedUnits = $releasedUnitsQuery
                            ->where('last_completed_order_id', $order->id)
                            ->get();

                        foreach ($releasedUnits as $unit) {
                            $previousOwners = $unit->previous_company_id_owners ?? [];
                            if (! in_array($order->company_id, $previousOwners)) {
                                throw new Exception(
                                    'Rotation is invalid: The owner must be assigned as a previous owner of the unit.'
                                );
                            }
                        }
                    }

                    // Post-operation consistency checks
                    $actualReleaseCount = $releasedUnitsQuery->count();
                    $insertedInventoryCount = LocalMarketOrderHasInventory::where('local_market_order_id', $order->id)->count();

                    if ($insertedInventoryCount !== $expectedInventoryCount) {
                        throw new Exception(
                            "Inventory insertion mismatch: Expected {$expectedInventoryCount} records, but found {$insertedInventoryCount}."
                        );
                    }

                    if ($expectedReleaseCount !== $actualReleaseCount) {
                        throw new Exception(
                            "Unit release mismatch: Expected {$expectedReleaseCount} released units, but found {$actualReleaseCount}."
                        );
                    }

                    Log::channel('local_market')->info('Order processed successfully', ['order_id' => $order->id]);
                    DB::commit();

                } catch (\Throwable $e) {
                    DB::rollBack();

                    Log::channel('local_market')->error('Error processing order', [
                        'order_id' => $order->id,
                        'code' => $e->getCode(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        });

        Log::channel('local_market')->info('Migration completed: Corrupted orders and inventory units handled.');
    }

    public function down(): void
    {
        // No rollback - corrective migration only
    }
};
