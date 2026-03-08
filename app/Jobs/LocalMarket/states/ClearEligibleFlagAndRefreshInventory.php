<?php

namespace App\Jobs\LocalMarket\states;

use App\Models\LocalMarketEligibleQuantity;
use App\Models\LocalMarketInventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ClearEligibleFlagAndRefreshInventory extends BaseStatus implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        protected int $localMarketOrderId,
        protected int $inventoryId
    ) {
        parent::__construct($this->localMarketOrderId);

        $delay = (int) config('trader.providers.lynk.refresh_inventory_stock_delay');
        $this->delay = Carbon::now()->addSeconds($delay); // must used it to make delay between orders to check the latest order  == touched by

        Log::channel(self::LOG_CHANNEL)->info('fire job ClearEligibleFlagAndRefreshInventory with Delay', [
            'localMarketOrderId' => $this->localMarketOrderId,
            'inventoryId' => $this->inventoryId,
            'delay' => $this->delay,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->onQueue('refresh_eligibilities');
    }

    public function handle(): void
    {

        $isLatestOrderTouched = LocalMarketEligibleQuantity::where('inventory_id', $this->inventoryId)->where('touched_by', $this->localMarketOrderID)->exists();
        if ($isLatestOrderTouched) {
            Log::channel(self::LOG_CHANNEL)->info('we will start to refresh inventory stock', [
                'order_id' => $this->localMarketOrderId,
                'inventory_id' => $this->inventoryId,
            ]);

            DB::transaction(function () {
                $clearedRows = $this->clearEligibleQuantityFlags();
                if ($clearedRows > 0) {
                    Log::channel(self::LOG_CHANNEL)->info('we finished to clear eligible quantity flags', [
                        'order_id' => $this->localMarketOrderId,
                        'inventory_id' => $this->inventoryId,
                        'rows_cleared' => $clearedRows,
                    ]);

                    $inventory = LocalMarketInventory::query()
                        ->whereKey($this->inventoryId)
                        ->firstOrFail();

                    if ($inventory->canBeEditable()) {
                        Log::channel(self::LOG_CHANNEL)->info('Inventory already refreshed', [
                            'inventory_id' => $this->inventoryId,
                            'order_id' => $this->localMarketOrderId,
                        ]);

                        return;
                    }

                    $inventory->markAsEditable();
                    $inventory->refreshStockQuantities(true);

                    Log::channel(self::LOG_CHANNEL)->info('Inventory refreshed', [
                        'inventory_id' => $this->inventoryId,
                        'order_id' => $this->localMarketOrderId,
                    ]);

                    return;
                }

                Log::channel(self::LOG_CHANNEL)->info('no clear rows', [
                    'inventory_id' => $this->inventoryId,
                    'order_id' => $this->localMarketOrderId,
                    'current_touched_by' => $this->getCurrentTouchedByValue(),
                ]);
            });
        } else {
            Log::channel(self::LOG_CHANNEL)->info('the latest order is not touched by this inventory', [
                'inventory_id' => $this->inventoryId,
                'order_id' => $this->localMarketOrderId,
                'current_touched_by' => $this->getCurrentTouchedByValue(),
            ]);

            return;
        }

    }

    public function failed(Throwable $exception): void
    {
        Log::channel(self::LOG_CHANNEL)->error('ClearEligibleFlagAndRefreshInventory failed', [
            'order_id' => $this->localMarketOrderId,
            'inventory_id' => $this->inventoryId,
            'error' => $exception->getMessage(),
        ]);

        $inventory = LocalMarketInventory::find($this->inventoryId);
        if ($inventory) {
            $inventory->markAsEditable();
            $inventory->refreshStockQuantities(true);
            Log::channel(self::LOG_CHANNEL)->info('finished to refreshing inventory stock', [
                'inventory_id' => $this->inventoryId,
                'order_id' => $this->localMarketOrderId,
            ]);
        }

    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return "clear-eligible:{$this->localMarketOrderId}:{$this->inventoryId}";
    }

    private function clearEligibleQuantityFlags(): int
    {
        return LocalMarketEligibleQuantity::query()
            ->where('inventory_id', $this->inventoryId)
            ->where('touched_by', $this->localMarketOrderId)
            ->update([
                'touched_by' => null,
            ]);
    }

    private function getCurrentTouchedByValue(): ?int
    {
        return LocalMarketEligibleQuantity::query()
            ->where('inventory_id', $this->inventoryId)
            ->whereNotNull('touched_by')
            ->value('touched_by');
    }
}
