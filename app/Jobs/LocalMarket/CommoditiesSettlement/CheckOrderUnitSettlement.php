<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement;

use App\Jobs\LocalMarket\CommoditiesSettlement\Enums\UnitSettlementStatus;
use App\Jobs\LocalMarket\CommoditiesSettlement\Exceptions\CheckOrderUnitSettlementException;
use App\Models\LocalMarketOrderHasUnit;
use Exception;

class CheckOrderUnitSettlement extends BaseCommoditiesSettlement
{
    public function __construct(protected int $localMarketOrderId, private ?int $inventoryId = null)
    {
        parent::__construct($localMarketOrderId);
    }

    public function handle(): void
    {
        try {
            $this->processOrderUnits($this->localMarketOrderId, $this->inventoryId);
            ValidateCommoditiesSettlement::dispatch($this->localMarketOrderId);
        } catch (Exception $e) {
            self::logError('CheckOrderUnitSettlement failed', [
                'order_id' => $this->localMarketOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new CheckOrderUnitSettlementException($e->getMessage());
        }
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
            ->where('local_market_order_id', $orderId)
            ->whereNull('settlement_status')
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

        if (is_null($inventoryUnit)) {
            $this->changeSettlementStatus($unit, UnitSettlementStatus::DeletedBySupplier);
        } elseif ($unit->local_market_order_id != $inventoryUnit->last_purchasing_order_id) {
            $this->changeSettlementStatus($unit, UnitSettlementStatus::SoldToAnotherCustomer);
        }
    }

    private function changeSettlementStatus(LocalMarketOrderHasUnit $unit, string $status): void
    {
        $unit->update([
            'settlement_status' => $status,
        ]);

        self::logInfo("Updated settlement_status to $status", [
            'unit_id' => $unit->id,
        ]);
    }
}
