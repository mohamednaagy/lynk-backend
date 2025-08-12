<?php

namespace App\Jobs\LocalMarket;

use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\OrderService;
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

    public $timeout = [60, 120, 180];

    /** @var OrderService */
    private $orderService;

    public function __construct(
        public readonly int $localMarketOrderId
    ) {
        $this->orderService = app(OrderService::class);
        $this->onQueue('local_market');
    }

    public function handle(): void
    {
        try {
            $order = LocalMarketOrder::findOrFail($this->localMarketOrderId);

            // Only insert order inventories and order units
            $this->orderService->insertOrderInventories($order);
            $this->orderService->insertOrderUnits($order);
        } catch (\Throwable $e) {
            Log::channel('local_market')->error('InsertOrderInventoriesAndUnits failed', [
                'order_id' => $this->localMarketOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
