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

            $this->processOrderUnits($localMarketOrder, $timestamp, $batchSize);

            $this->validateUnitInsertions($localMarketOrder);
        } catch (Exception $e) {
            $this->logOrderUnitsError($localMarketOrder, $e);
            throw $e;
        }
    }

    private function processOrderUnits(LocalMarketOrder $localMarketOrder, string $timestamp, int $batchSize): void
    {
        DB::table('local_market_inventory_units')
            ->select(['id', 'local_market_inventory_id'])
            ->where('hold_for', $localMarketOrder->id)
            ->orderBy('id')
            ->chunk($batchSize, function ($chunk) use ($localMarketOrder, $timestamp) {
                $insertData = $this->prepareOrderUnitInsertData($chunk, $localMarketOrder, $timestamp);
                DB::table('local_market_order_has_units')->insert($insertData);
            });
    }

    private function prepareOrderUnitInsertData($chunk, LocalMarketOrder $localMarketOrder, string $timestamp): array
    {
        return collect($chunk)->map(function ($unit) use ($localMarketOrder, $timestamp) {
            return [
                'unit_id' => $unit->id,
                'inventory_id' => $unit->local_market_inventory_id,
                'local_market_order_id' => $localMarketOrder->id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        })->toArray();
    }

    private function validateUnitInsertions(LocalMarketOrder $localMarketOrder): void
    {
        $totalUnits = $this->countHeldUnits($localMarketOrder);
        $insertedCount = $this->countInsertedUnits($localMarketOrder);

        $this->logUnitInsertionDetails($localMarketOrder, $insertedCount, $totalUnits);

        if ($insertedCount !== $totalUnits) {
            throw new Exception(
                "Units count mismatch. Expected: {$totalUnits}, Inserted: {$insertedCount}"
            );
        }
    }

    private function countHeldUnits(LocalMarketOrder $localMarketOrder): int
    {
        return DB::table('local_market_inventory_units')->where('hold_for', $localMarketOrder->id)->count();
    }

    private function countInsertedUnits(LocalMarketOrder $localMarketOrder): int
    {
        return DB::table('local_market_order_has_units')->where('local_market_order_id', $localMarketOrder->id)->count();
    }

    private function logUnitInsertionDetails(LocalMarketOrder $localMarketOrder, int $insertedCount, int $totalUnits): void
    {
        log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('insertOrderUnits', $localMarketOrder), [
            'localMarketOrderId' => $localMarketOrder->id,
            'insertedCount' => $insertedCount,
            'totalUnits' => $totalUnits,
        ]);
    }

    private function logOrderUnitsError(LocalMarketOrder $localMarketOrder, Exception $e): void
    {
        log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLocalMarketOrderTitle('Error in insertOrderUnits', $localMarketOrder), [
            'localMarketOrderId' => $localMarketOrder->id,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Insert order inventories into the database.
     *
     * @throws \Exception
     */
    public function insertOrderInventories(LocalMarketOrder $localMarketOrder): void
    {
        try {
            $timestamp = now()->format('Y-m-d H:i:s');
            $inventories = OrderCommoditiesDto::getInventoriesFromOrder($localMarketOrder);

            collect($inventories)
                ->sortKeys()
                ->map(function (array $data, int $inventoryId) use ($localMarketOrder, $timestamp): array {
                    return [
                        'local_market_order_id' => $localMarketOrder->id,
                        'local_market_inventory_id' => $inventoryId,
                        'quantity' => $data['numberOfSuitableUnits'],
                        'supplier_id' => $data['supplier']['id'],
                        'price' => $data['price'],
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                })
                ->chunk(1000)->each(function ($chunk): void {
                    DB::table('local_market_order_has_inventories')->insert(
                        $chunk->toArray()
                    );
                });

            $expectedInventoryCount = count($inventories);
            $insertedInventoryCount = LocalMarketOrderHasInventory::where('local_market_order_id', $localMarketOrder->id)->count();

            if ($insertedInventoryCount !== $expectedInventoryCount) {
                throw new Exception(
                    "Inventory insertion mismatch: Expected {$expectedInventoryCount} records, but only {$insertedInventoryCount} were found in the database."
                );
            }
        } catch (\Exception $e) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLocalMarketOrderTitle('Error in insertOrderInventories', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function changeOrderStatus($order, $status)
    {
        $order->status = $status;
        $order->save();
    }
}
