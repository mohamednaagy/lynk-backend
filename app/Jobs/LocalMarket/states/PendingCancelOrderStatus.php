<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Enums\LocalMarket\OrderCancelledBy;
use App\Enums\LocalMarket\OrderCancelReason;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\InventoryService;
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

    private LocalMarketWebhook $localMarketWebhook;

    public function __construct(
        private LocalMarketOrder $localMarketOrder
    ) {
        $this->inventoryService = app(InventoryService::class);
        $this->localMarketWebhook = app(LocalMarketWebhook::class);

        $this->onQueue('local_market');
        $this->logQueueJob();
    }

    public function handle(): void
    {
        DB::beginTransaction();

        try {
            $this->inventoryService->freeOrderInventoryUnits($this->localMarketOrder);
            $this->logCancellationDetails();

            DB::commit();

            $this->localMarketOrder->changeStatusTo(LocalMarketOrderStatus::Cancelled);
            $this->localMarketWebhook->with(['case' => LocalMarketOrderStatus::Cancelled])->handle();

            $this->logQueueJob('Order cancelled successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->handleCancellationFailure($e);
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

    /**
     * Handle order cancellation failure.
     */
    private function handleCancellationFailure(\Throwable $e): void
    {
        $this->localMarketOrder->changeStatusTo(LocalMarketOrderStatus::FailedToCancel);
        Log::channel('local_market')->error('Failed to cancel order.', [
            'order_id' => $this->localMarketOrder->id,
            'error_message' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString(),
        ]);
    }
}
