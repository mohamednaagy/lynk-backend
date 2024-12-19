<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\LocalMarket\OrderCancelledBy;
use App\Enums\LocalMarket\OrderCancelReason;
use App\Enums\LocalMarket\OrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\OwnershipService;
use App\Services\LocalMarket\UnitService;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PendingCancelOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, LocalMarketHelperTrait, Queueable, SerializesModels;

    private InventoryService $inventoryService;

    private OwnershipService $ownershipService;

    private UnitService $unitService;

    private LocalMarketWebhook $localMarketWebhook;

    private ?int $cancelReason;

    public function __construct(
        private LocalMarketOrder $localMarketOrder,
        $cancelReason = null
    ) {
        $this->inventoryService = app(InventoryService::class);
        $this->localMarketWebhook = app(LocalMarketWebhook::class);
        $this->ownershipService = app(OwnershipService::class);
        $this->unitService = app(UnitService::class);

        $this->cancelReason = $cancelReason;
        $this->onQueue('local_market');
        $this->logQueueJob();
    }

    public function handle(): void
    {
        DB::beginTransaction();
        try {
            $this->logCancellationDetails();
            Log::channel('local_market')->info("add cancel log data to db successfully {$this->localMarketOrder->id}");
            $this->unitService->revertInventoryUnitOwnership($this->localMarketOrder);
            Log::channel('local_market')->info("swap ownership successfully {$this->localMarketOrder->id}");
            $this->inventoryService->cancelOrderUnits($this->localMarketOrder);
            Log::channel('local_market')->info("refresh order inventories and units successfully {$this->localMarketOrder->id}");
            DB::commit();
            $this->localMarketOrder->changeStatusTo(OrderStatus::Cancelled);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::channel('local_market')->error("failed to exec transaction at pending cancel local market order id {$this->localMarketOrder->id}", ['message' => $e->getMessage()]);
            $this->localMarketOrder->changeStatusTo(OrderStatus::FailedToCancel);
        }
    }

    private function logCancellationDetails(): void
    {
        $this->localMarketOrder->cancelOrder()->create([
            'cancelled_by' => OrderCancelledBy::Customer,
            'cancel_reason' => OrderCancelReason::CancelOrder,
        ]);
    }

    private function logQueueJob(?string $message = 'Cancel order status job added to queue local_market'): void
    {
        Log::channel('local_market')->info(
            "$message",
            ['order_id' => $this->localMarketOrder->id]
        );
    }
}
