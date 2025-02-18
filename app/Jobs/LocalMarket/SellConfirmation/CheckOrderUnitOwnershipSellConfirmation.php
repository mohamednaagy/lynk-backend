<?php

namespace App\Jobs\LocalMarket\SellConfirmation;

use App\Enums\LocalMarketOrderStatus;
use App\Jobs\LocalMarket\SellConfirmation\Enums\UnitOwnershipStatus;
use App\Models\LocalMarketOrder;
use App\Models\LocalMarketOrderHasUnit;
use Illuminate\Support\Collection;

class CheckOrderUnitOwnershipSellConfirmation extends BaseSellConfirmation
{
    public function __construct(private ?int $inventoryId = null)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        LocalMarketOrder::whereIn('status', [LocalMarketOrderStatus::CommoditiesSell, LocalMarketOrderStatus::Completed])
            ->chunkById(self::CHUNK_SIZE, function ($orders) {
                $this->processOrders($orders);
            });

        // Continue the process of sell-confirmation certificate
        ValidateOrderUnitsEligibility::dispatch();
    }

    public function uniqueId(): string
    {
        return $this->inventoryId ?
            __CLASS__.'_'.$this->inventoryId :
            __CLASS__;
    }

    private function processOrders(Collection $orders): void
    {
        foreach ($orders as $order) {
            $this->processOrderUnits($order);
        }
    }

    private function processOrderUnits(LocalMarketOrder $order): void
    {
        LocalMarketOrderHasUnit::with('inventoryUnit')
            ->when($this->inventoryId, fn ($q) => $q->where('inventory_id', $this->inventoryId))
            ->where([
                'local_market_order_id' => $order->id,
                'ownership_status' => UnitOwnershipStatus::Owner,
            ])
            ->chunkById(self::CHUNK_SIZE, function ($units) {
                self::logInfo('Processing chunk of units');
                foreach ($units as $unit) {
                    $this->processUnit($unit);
                }
            });
    }

    private function processUnit(LocalMarketOrderHasUnit $unit): void
    {
        $inventoryUnit = $unit->inventoryUnit;
        self::logInfo('Processing unit', [
            'unit_id' => $unit->id,
            'order_id' => $unit->local_market_order_id,
            'inventory_unit_id' => optional($inventoryUnit)->id,
            'inventory_deleted_at' => optional($inventoryUnit)->deleted_at,
            'inventory_last_purchasing_order_id' => optional($inventoryUnit)->last_purchasing_order_id,
        ]);

        if (is_null($inventoryUnit)) {
            $this->markUnitDeleted($unit);
        } elseif ($unit->local_market_order_id != $inventoryUnit->last_purchasing_order_id) {
            $this->markUnitSold($unit);
        }
    }

    private function markUnitDeleted(LocalMarketOrderHasUnit $unit): void
    {
        // Deleted by supplier
        $unit->update([
            'ownership_status' => UnitOwnershipStatus::DeletedBySupplier,
        ]);

        self::logInfo('Updated status to DeletedBySupplier', [
            'unit_id' => $unit->id,
        ]);
    }

    private function markUnitSold(LocalMarketOrderHasUnit $unit): void
    {
        // Sold to another customer
        $unit->update([
            'ownership_status' => UnitOwnershipStatus::SoldToAnotherCustomer,
        ]);

        self::logInfo('Updated status to SoldToAnotherCustomer', [
            'unit_id' => $unit->id,
        ]);
    }
}
