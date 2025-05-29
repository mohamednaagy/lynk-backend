<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement;

use App\Jobs\LocalMarket\CommoditiesSettlement\Enums\CommoditySettlementStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchOrderSettlementCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable;

    private const CHUNK_SIZE = 10;
    private const QUEUE_NAME = 'local_market_commodities_settlement';

    /**
     * Create a new job instance.
     *
     * @param int|null $mainLocalMarketOrderId The ID of the current purchased local market order.
     * @param int|null $inventoryId The ID of the inventory to filter.
     */
    public function __construct(
        private ?int $mainLocalMarketOrderId = null,
        private ?int $inventoryId = null
    )
    {
        $this->onQueue(self::QUEUE_NAME);

        Log::channel('local_market')->info(
            'CommoditiesSettlement - Added job to queue',
            [
                'job' => class_basename(static::class),
                'queue' => self::QUEUE_NAME,
                'main_local_market_order_id' => $this->mainLocalMarketOrderId,
                'inventory_id' => $this->inventoryId,
            ]
        );
    }

    /**
     * Handle the job.
     */
    public function handle(): void
    {
        LocalMarketOrder::query()
            ->where('commodities_settlement_status', CommoditySettlementStatus::PendingSettlement)
            ->when($this->mainLocalMarketOrderId, function ($query) {
                // This check ensures that we retrieve only the orders affected by the main purchasing trader order.
                // NOTE: We cannot use a relationship like inventoryUnits here, as the unit is associated with a different order.
                $query->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('local_market_inventory_units')
                        ->where('hold_for', $this->mainLocalMarketOrderId)
                        ->whereNull('deleted_at');
                });
            })
            ->when($this->inventoryId, function ($query) {
                // This check ensures that we retrieve only the orders affected by the main purchasing trader order.
                // NOTE: We cannot use a relationship like inventoryUnits here, as the unit is associated with a different order.
                $query->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('local_market_inventory_units')
                        ->where('local_market_inventory_id', $this->inventoryId)
                        ->whereNull('deleted_at');
                });
            })->chunkById(self::CHUNK_SIZE, function ($orders) {
                foreach ($orders as $order) {
                    CheckOrderUnitSettlement::dispatch($order->id, $this->inventoryId);
                }
            });
    }

    /**
     * Define middleware for the job.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->uniqueId()),
        ];
    }

    /**
     * Generate a unique identifier for job deduplication.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        $parts = [__CLASS__];

        if ($this->inventoryId) {
            $parts[] = $this->inventoryId;
        }

        if ($this->mainLocalMarketOrderId) {
            $parts[] = 'order_' . $this->mainLocalMarketOrderId;
        }

        return implode('_', $parts);
    }
}
