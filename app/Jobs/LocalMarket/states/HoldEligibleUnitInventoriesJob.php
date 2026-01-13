<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarket\OrderStatus;
use App\Exceptions\LocalMarket\FailedToHoldRequiredUnitsException;
use App\Services\LocalMarket\UnitService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HoldEligibleUnitInventoriesJob extends BaseStatus implements ShouldBeUnique
{
    protected function setUp(): void
    {
        $this->onQueue('hold_eligible_units_local_market_orders');
        $this->logQueueJob();
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        DB::beginTransaction();

        try {
            app(UnitService::class)->holdEligibleUnits($this->localMarketOrder);
            DB::commit();

            EligibleCommoditiesFoundStatus::dispatch($this->localMarketOrder->id);
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $eligibleInventories = $this->localMarketOrder->data['inventories'] ?? [];

        try {
            DB::transaction(fn () => $this->restoreHeldQuantities($eligibleInventories));
        } catch (Throwable $e) {
            Log::channel('local_market')->critical('Failed to restore held quantities after job failure', [
                'order_id' => $this->localMarketOrder->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::channel('local_market')->error('Error in HoldEligibleUnitInventoriesJob', [
            'order_id' => $this->localMarketOrder->id,
            'error' => $exception->getMessage(),
            'trace' => config('app.debug') ? $exception->getTraceAsString() : 'Hidden in production',
        ]);

        $this->localMarketOrder->update([
            'status' => $this->getOrderStatus($exception),
        ]);
    }

    /**
     * Restore held eligible quantities for the failed job.
     */
    private function restoreHeldQuantities(array $eligibleInventories): void
    {
        if (empty($eligibleInventories)) {
            return;
        }

        $cases = '';
        $ids = [];
        $bindings = [];

        foreach ($eligibleInventories as $inventoryId => $inventory) {
            if (! isset($inventory['numberOfSuitableUnits'])) {
                continue;
            }

            $cases .= 'WHEN inventory_id = ? THEN ? ';
            $bindings[] = $inventoryId;
            $bindings[] = $inventory['numberOfSuitableUnits'];
            $ids[] = $inventoryId;
        }

        if (empty($ids)) {
            return;
        }

        $idsPlaceholder = implode(',', array_fill(0, count($ids), '?'));
        $bindings = array_merge($bindings, $ids, [$this->localMarketOrder->id]);

        $sql = "
            UPDATE local_market_eligible_quantities
            SET
                eligible_quantity = eligible_quantity + CASE $cases ELSE 0 END,
                touched_by = NULL,
                updated_at = NOW()
            WHERE inventory_id IN ($idsPlaceholder)
              AND touched_by = ?
        ";

        $affected = DB::update($sql, $bindings);

        Log::channel('local_market')->info('Restored held eligible quantities after failure', [
            'order_id' => $this->localMarketOrder->id,
            'affected_rows' => $affected,
            'inventory_ids' => $ids,
            'inventories_count' => count($eligibleInventories),
        ]);
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->localMarketOrder->id;
    }

    private function getOrderStatus($exception)
    {
        return $exception instanceof FailedToHoldRequiredUnitsException
                ? OrderStatus::NoEligibleCommoditiesAvailable
                : OrderStatus::FailedPurchase;
    }
}
