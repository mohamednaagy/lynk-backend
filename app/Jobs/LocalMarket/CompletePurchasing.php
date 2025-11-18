<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarket\OrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\UnitService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CompletePurchasing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $localMarketOrderId
    ) {
        $this->onQueue('local_market_order_inventories_units_logging');
    }

    public function handle(): void
    {
        try {
            $localMarketOrder = LocalMarketOrder::findOrFail($this->localMarketOrderId);
            $data = UnitService::getUnitsByGroupedByPreviousOwner($localMarketOrder);
            $localMarketOrder->update([
                'status' => OrderStatus::CommoditiesPurchased,
                'data' => array_merge($localMarketOrder->data, ['data' => $data]),
            ]);

            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('unit service for order '.$localMarketOrder->id, $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLocalMarketOrderTitle('CompletePurchasing failed', $localMarketOrder), [
                'localMarketOrderId' => $this->localMarketOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
