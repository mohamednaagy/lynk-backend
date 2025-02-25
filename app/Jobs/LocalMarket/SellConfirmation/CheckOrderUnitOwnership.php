<?php

namespace App\Jobs\LocalMarket\SellConfirmation;

use App\Jobs\LocalMarket\SellConfirmation\Enums\UnitOwnershipStatus;
use App\Models\LocalMarketOrderHasUnit;

class CheckOrderUnitOwnership extends BaseSellConfirmation
{
    public function __construct(private int $localMarketOrderId, private ?int $inventoryId = null)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->processOrderUnits($this->localMarketOrderId, $this->inventoryId);
        ValidateOrderEligibility::dispatch($this->localMarketOrderId);
    }

    public function uniqueId(): string
    {
        return $this->localMarketOrderId ?
            __CLASS__.'_'.$this->localMarketOrderId
            : parent::uniqueId();
    }

    private function processOrderUnits(int $orderId, ?int $inventoryId = null): void
    {
        LocalMarketOrderHasUnit::with('inventoryUnit')
            ->when($inventoryId, fn ($q) => $q->where('inventory_id', $inventoryId))
            ->where([
                'local_market_order_id' => $orderId,
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
        $unit->update([
            'ownership_status' => UnitOwnershipStatus::DeletedBySupplier,
        ]);

        self::logInfo('Updated status to DeletedBySupplier', [
            'unit_id' => $unit->id,
        ]);
    }

    private function markUnitSold(LocalMarketOrderHasUnit $unit): void
    {
        $unit->update([
            'ownership_status' => UnitOwnershipStatus::SoldToAnotherCustomer,
        ]);

        self::logInfo('Updated status to SoldToAnotherCustomer', [
            'unit_id' => $unit->id,
        ]);
    }
}
