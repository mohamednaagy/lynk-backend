<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarket\OrderStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\OrderService;
use App\Services\LocalMarket\UnitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class InsertOrderInventoriesAndUnits implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [5, 10, 15];

    /** @var OrderService */
    private $orderService;

    /** @var UnitService */
    private $unitService;

    public function __construct(
        public readonly int $localMarketOrderId
    ) {
        $this->orderService = app(OrderService::class);
        $this->unitService = app(UnitService::class);
        $this->onQueue('local_market_order_inventories_units_logging');
    }

    public function handle(): void
    {
        try {
            $order = LocalMarketOrder::findOrFail($this->localMarketOrderId);

            $this->orderService->insertOrderInventories($order);
            $this->orderService->insertOrderUnits($order);
            $this->unitService->changeOrderUnitsOwnershipTo(
                $order,
                OwnershipTypes::Company,
                $order->lender_identifier,
                UnitOwnershipAction::PurchaseCommodity
            );

            $data = UnitService::getUnitsByGroupedByPreviousOwner($order);

            $order->update([
                'status' => OrderStatus::CommoditiesPurchased,
                'data' => array_merge($order->data, ['data' => $data]),
            ]);

            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('unit service for order '.$order->id, $order), [
                'localMarketOrderId' => $order->id,
                'data' => $data,
            ]);

        } catch (\Throwable $e) {
            if ($order) {
                $order->update([
                    'status' => OrderStatus::FailedPurchase,
                ]);
            }
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error('InsertOrderInventoriesAndUnits failed for localMarketOrderId => '.$this->localMarketOrderId, [
                'localMarketOrderId' => $this->localMarketOrderId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
